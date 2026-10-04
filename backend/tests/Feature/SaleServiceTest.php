<?php

namespace Tests\Feature;

use App\Enums\PaymentMethod; // formas de pagamento usadas nas chamadas ao service
use App\Models\Product; // produtos de teste (criados pela factory)
use App\Models\Sale; // para contar e ler as vendas gravadas
use App\Models\SaleItem; // para contar e ler os itens gravados
use App\Services\SaleService; // a classe testada
use Illuminate\Database\QueryException; // erro do banco (usado no teste de rollback)
use Illuminate\Foundation\Testing\RefreshDatabase; // banco limpo e isolado a cada teste
use Illuminate\Validation\ValidationException; // erro de regra de negócio (vira HTTP 422)
use PHPUnit\Framework\Attributes\DataProvider; // roda o mesmo teste com dados diferentes
use Tests\TestCase; // classe base de testes do Laravel

// === REGRAS DE NEGÓCIO DO SaleService ===
class SaleServiceTest extends TestCase
{
    // Cada teste começa com as tabelas vazias. O phpunit.xml aponta para SQLite em
    // memória, então isto NÃO toca no banco de desenvolvimento (database.sqlite).
    use RefreshDatabase;

    private SaleService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new SaleService();
    }

    // 1. Venda válida: total e subtotais vêm do preço do BANCO; snapshots são gravados
    public function test_calculates_totals_from_database_prices_and_saves_snapshots(): void
    {
        [$a, $b] = $this->makeProducts();

        // O primeiro item chega com um "price" forjado de 1 centavo: o service o ignora
        $sale = $this->service->create([
            ['product_id' => $a->id, 'quantity' => 2, 'price' => 1],
            ['product_id' => $b->id, 'quantity' => 3],
        ], PaymentMethod::Credit);

        $this->assertSame(2750, $sale->total); // 2 x 1000 + 3 x 250
        $this->assertCount(2, $sale->items);

        $this->assertDatabaseHas('sale_items', [
            'sale_id' => $sale->id, 'product_id' => $a->id,
            'product_name' => 'Produto A', 'unit_price' => 1000, 'quantity' => 2, 'subtotal' => 2000,
        ]);
        $this->assertDatabaseHas('sale_items', [
            'sale_id' => $sale->id, 'product_id' => $b->id,
            'product_name' => 'Produto B', 'unit_price' => 250, 'quantity' => 3, 'subtotal' => 750,
        ]);
    }

    // 2. Produto indisponível ou inexistente: recusa, aponta a linha certa e não grava nada.
    //    Um carrinho misto também prova que TODOS os itens inválidos são reportados de uma vez.
    public function test_rejects_unavailable_and_nonexistent_products_without_recording(): void
    {
        [$ok] = $this->makeProducts();
        $unavailable = Product::factory()->unavailable()->create(['name' => 'Queijo Teste']);

        $errors = $this->validationErrorsFrom(fn () => $this->service->create([
            ['product_id' => $ok->id, 'quantity' => 1],          // índice 0: válido
            ['product_id' => $unavailable->id, 'quantity' => 1], // índice 1: indisponível
            ['product_id' => 999999, 'quantity' => 1],           // índice 2: inexistente
        ], PaymentMethod::Credit));

        $this->assertArrayNotHasKey('items.0.product_id', $errors);
        $this->assertArrayHasKey('items.1.product_id', $errors);
        $this->assertStringContainsString('Queijo Teste', $errors['items.1.product_id'][0]);
        $this->assertArrayHasKey('items.2.product_id', $errors);
        $this->assertNothingRecorded();
    }

    // 3. Defesa do service contra venda sem itens (mesmo chamado direto, sem o Form Request)
    public function test_rejects_empty_items(): void
    {
        $errors = $this->validationErrorsFrom(fn () => $this->service->create([], PaymentMethod::Cash, 1000));

        $this->assertArrayHasKey('items', $errors);
        $this->assertNothingRecorded();
    }

    // 4. Dinheiro: valor insuficiente ou ausente é recusado; senão calcula o troco.
    //    Carrinho padrão = R$ 27,50 (2750 centavos).
    #[DataProvider('cashScenarios')]
    public function test_cash_payment_validates_amount_and_calculates_change(?int $received, ?int $expectedChange): void
    {
        [$a, $b] = $this->makeProducts();

        if ($expectedChange === null) {
            $errors = $this->validationErrorsFrom(fn () => $this->service->create($this->cart($a, $b), PaymentMethod::Cash, $received));

            $this->assertArrayHasKey('amount_received', $errors);
            $this->assertNothingRecorded();

            return;
        }

        $sale = $this->service->create($this->cart($a, $b), PaymentMethod::Cash, $received);

        $this->assertSame($expectedChange, $sale->change_amount);
        // Confere o que o banco guardou, inclusive o TEXTO da forma de pagamento
        $this->assertDatabaseHas('sales', [
            'id' => $sale->id, 'payment_method' => 'cash', 'total' => 2750,
            'amount_received' => $received, 'change_amount' => $expectedChange,
        ]);
    }

    // [valor recebido em centavos, troco esperado | null quando a venda deve ser recusada]
    public static function cashScenarios(): array
    {
        return [
            'insuficiente (falta 1 centavo)' => [2749, null],
            'sem valor recebido' => [null, null],
            'exato (troco zero)' => [2750, 0],
            'com troco' => [5000, 2250],
        ];
    }

    // 5. Cartão: o valor recebido é descartado (null) e o troco é zero
    #[DataProvider('cardMethods')]
    public function test_card_payment_stores_null_amount_received_and_zero_change(PaymentMethod $method, string $stored): void
    {
        [$a, $b] = $this->makeProducts();

        // Mesmo com um valor informado, em cartão ele não é gravado
        $sale = $this->service->create($this->cart($a, $b), $method, 99999);

        $this->assertNull($sale->fresh()->amount_received);
        $this->assertDatabaseHas('sales', [
            'id' => $sale->id, 'payment_method' => $stored, 'change_amount' => 0,
        ]);
    }

    public static function cardMethods(): array
    {
        return [
            'credito' => [PaymentMethod::Credit, 'credit'],
            'debito' => [PaymentMethod::Debit, 'debit'],
        ];
    }

    // 6. Preço histórico: mudar o produto depois da venda não altera a venda
    public function test_keeps_historical_price_after_product_changes(): void
    {
        [$a, $b] = $this->makeProducts();
        $sale = $this->service->create($this->cart($a, $b), PaymentMethod::Credit);

        $a->update(['name' => 'Produto A Renomeado', 'price' => 9999]);

        $item = SaleItem::where('sale_id', $sale->id)->where('product_id', $a->id)->first();
        $this->assertSame(1000, $item->unit_price);          // snapshot do preço
        $this->assertSame('Produto A', $item->product_name); // snapshot do nome
        $this->assertSame(9999, $item->product->price);      // o catálogo mudou, o item não
        $this->assertSame(2750, $sale->fresh()->total);
    }

    // 7. Atomicidade: uma falha DEPOIS de a venda ser inserida desfaz tudo.
    //    O mesmo produto duas vezes viola o índice único (sale_id, product_id) ao gravar
    //    o segundo item; a transação deve desfazer a venda e o primeiro item.
    public function test_rolls_back_everything_when_saving_fails(): void
    {
        [$a] = $this->makeProducts();

        try {
            $this->service->create([
                ['product_id' => $a->id, 'quantity' => 1],
                ['product_id' => $a->id, 'quantity' => 2], // repetido: o banco recusa
            ], PaymentMethod::Credit);
            $this->fail('Deveria ter lançado QueryException pelo índice único.');
        } catch (QueryException) {
            // esperado
        }

        $this->assertNothingRecorded();
    }

    // === AUXILIARES ===

    // Dois produtos disponíveis: A = R$ 10,00 e B = R$ 2,50
    private function makeProducts(): array
    {
        return [
            Product::factory()->create(['name' => 'Produto A', 'price' => 1000]),
            Product::factory()->create(['name' => 'Produto B', 'price' => 250]),
        ];
    }

    // Carrinho padrão: 2 x A + 3 x B = 2750 centavos
    private function cart(Product $a, Product $b): array
    {
        return [
            ['product_id' => $a->id, 'quantity' => 2],
            ['product_id' => $b->id, 'quantity' => 3],
        ];
    }

    // Executa a ação e devolve os erros da ValidationException (falha se for aceita)
    private function validationErrorsFrom(callable $action): array
    {
        try {
            $action();
        } catch (ValidationException $e) {
            return $e->errors();
        }

        $this->fail('Deveria ter lançado ValidationException, mas a operação foi aceita.');
    }

    // Uma venda recusada não pode deixar NENHUM registro
    private function assertNothingRecorded(): void
    {
        $this->assertSame(0, Sale::count());
        $this->assertSame(0, SaleItem::count());
    }
}
