<?php

namespace Tests\Feature;

use App\Enums\PaymentMethod;
use App\Models\Product;
use App\Models\Sale;
use App\Services\SaleService;
use Illuminate\Foundation\Testing\RefreshDatabase; // banco isolado (SQLite em memória)
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

// === API DE VENDAS (camada HTTP: Form Request + Controller + Resource) ===
class SaleApiTest extends TestCase
{
    use RefreshDatabase;

    // 1. Criação: 201, valores recalculados pelo backend e comprovante completo
    public function test_creates_sale_with_backend_prices_and_returns_receipt(): void
    {
        [$a, $b] = $this->makeProducts();

        // O cliente tenta forjar preço, subtotal e total: nada disso é lido
        $response = $this->postJson('/api/sales', [
            'items' => [
                ['product_id' => $a->id, 'quantity' => 2, 'price' => 1],
                ['product_id' => $b->id, 'quantity' => 3, 'subtotal' => 1],
            ],
            'payment_method' => 'cash',
            'amount_received' => 5000,
            'total' => 1,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.total', 2750)
            ->assertJsonPath('data.payment_method', 'cash')
            ->assertJsonPath('data.amount_received', 5000)
            ->assertJsonPath('data.change_amount', 2250)
            ->assertJsonCount(2, 'data.items')
            ->assertJsonPath('data.items.0.product_name', 'Produto A')
            ->assertJsonPath('data.items.0.unit_price', 1000)
            ->assertJsonPath('data.items.0.subtotal', 2000)
            ->assertJsonPath('data.items.1.subtotal', 750);

        $this->assertDatabaseCount('sales', 1);
        $this->assertDatabaseCount('sale_items', 2);
    }

    // 2. Consulta do comprovante: mostra o preço da época, e id inexistente dá 404
    public function test_shows_finalized_sale_with_historical_prices_and_404_for_unknown_id(): void
    {
        [$a] = $this->makeProducts();
        $sale = app(SaleService::class)->create([['product_id' => $a->id, 'quantity' => 1]], PaymentMethod::Credit);

        $a->update(['price' => 9999]); // o preço do catálogo muda depois da venda

        $this->getJson("/api/sales/{$sale->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $sale->id)
            ->assertJsonPath('data.total', 1000)
            ->assertJsonPath('data.items.0.unit_price', 1000);

        $this->getJson('/api/sales/999999')
            ->assertNotFound()
            ->assertJsonPath('message', 'Recurso não encontrado.');
    }

    // 3. Validação de formato: payload inválido gera 422 no campo certo e não grava nada
    #[DataProvider('invalidPayloads')]
    public function test_rejects_malformed_payload_with_422(array $payload, string $errorKey): void
    {
        $this->postJson('/api/sales', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors([$errorKey]);

        $this->assertSame(0, Sale::count());
    }

    public static function invalidPayloads(): array
    {
        $item = ['product_id' => 1, 'quantity' => 1];

        return [
            'sem itens' => [['items' => [], 'payment_method' => 'credit'], 'items'],
            'campo items ausente' => [['payment_method' => 'credit'], 'items'],
            'quantidade zero' => [['items' => [['product_id' => 1, 'quantity' => 0]], 'payment_method' => 'credit'], 'items.0.quantity'],
            'quantidade negativa' => [['items' => [['product_id' => 1, 'quantity' => -1]], 'payment_method' => 'credit'], 'items.0.quantity'],
            'quantidade acima do limite' => [['items' => [['product_id' => 1, 'quantity' => 1000]], 'payment_method' => 'credit'], 'items.0.quantity'],
            'quantidade decimal' => [['items' => [['product_id' => 1, 'quantity' => 1.5]], 'payment_method' => 'credit'], 'items.0.quantity'],
            'produto repetido' => [['items' => [$item, $item], 'payment_method' => 'credit'], 'items.0.product_id'],
            'forma de pagamento invalida' => [['items' => [$item], 'payment_method' => 'pix'], 'payment_method'],
            'dinheiro sem valor recebido' => [['items' => [$item], 'payment_method' => 'cash'], 'amount_received'],
            'valor recebido negativo' => [['items' => [$item], 'payment_method' => 'cash', 'amount_received' => -1], 'amount_received'],
        ];
    }

    // 4. Regras de negócio chegam como 422 em português e não gravam nada
    public function test_business_rule_violations_return_422_and_record_nothing(): void
    {
        [$a, $b] = $this->makeProducts();
        $unavailable = Product::factory()->unavailable()->create();

        $this->postJson('/api/sales', [
            'items' => [['product_id' => $unavailable->id, 'quantity' => 1]],
            'payment_method' => 'credit',
        ])->assertStatus(422)->assertJsonValidationErrors(['items.0.product_id']);

        // Total de R$ 27,50 com apenas R$ 27,49 recebidos
        $this->postJson('/api/sales', [
            'items' => [['product_id' => $a->id, 'quantity' => 2], ['product_id' => $b->id, 'quantity' => 3]],
            'payment_method' => 'cash',
            'amount_received' => 2749,
        ])->assertStatus(422)->assertJsonValidationErrors(['amount_received']);

        $this->assertDatabaseCount('sales', 0);
        $this->assertDatabaseCount('sale_items', 0);
    }

    // 5. Cartão pela API: amount_received é ignorado (sem erro), gravado como null, troco 0
    public function test_card_payment_ignores_amount_received(): void
    {
        [$a] = $this->makeProducts();

        $this->postJson('/api/sales', [
            'items' => [['product_id' => $a->id, 'quantity' => 1]],
            'payment_method' => 'debit',
            'amount_received' => 99999,
        ])->assertCreated()
            ->assertJsonPath('data.payment_method', 'debit')
            ->assertJsonPath('data.amount_received', null)
            ->assertJsonPath('data.change_amount', 0);
    }

    // 6. Venda finalizada não é alterada: não existem rotas de edição nem exclusão
    public function test_finalized_sales_cannot_be_modified_or_deleted(): void
    {
        [$a] = $this->makeProducts();
        $sale = app(SaleService::class)->create([['product_id' => $a->id, 'quantity' => 1]], PaymentMethod::Credit);

        foreach (['putJson', 'patchJson', 'deleteJson'] as $method) {
            $this->{$method}("/api/sales/{$sale->id}", ['total' => 1])->assertStatus(405);
        }
        $this->deleteJson('/api/sales')->assertStatus(405);

        $this->assertDatabaseHas('sales', ['id' => $sale->id, 'total' => 1000]);
        $this->assertDatabaseCount('sales', 1);
    }

    // Dois produtos disponíveis: A = R$ 10,00 e B = R$ 2,50
    private function makeProducts(): array
    {
        return [
            Product::factory()->create(['name' => 'Produto A', 'price' => 1000]),
            Product::factory()->create(['name' => 'Produto B', 'price' => 250]),
        ];
    }
}
