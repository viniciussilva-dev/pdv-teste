<?php

namespace App\Services;

use App\Enums\PaymentMethod; // forma de pagamento (cash, credit, debit)
use App\Models\Product; // catálogo: única fonte de preço e de disponibilidade
use App\Models\Sale; // venda gravada por este service
use Illuminate\Support\Facades\DB; // transação: ou grava tudo, ou não grava nada
use Illuminate\Validation\ValidationException; // gera o erro HTTP 422 no formato padrão do Laravel

class SaleService
{
    /**
     * Cria uma venda finalizada, recalculando tudo com os dados REAIS do banco.
     *
     * Pressupõe que a FORMA do payload já foi validada pelo Form Request (Etapa 5):
     * product_id sem repetição e quantity inteira de 1 a 999.
     * Aqui ficam as regras que dependem do banco e do dinheiro, mais uma defesa
     * contra venda sem itens (caso o service seja chamado diretamente).
     * Preços nunca entram: o parâmetro $items só carrega product_id e quantity.
     *
     * @param  array<int, array{product_id: int, quantity: int}>  $items
     * @param  int|null  $amountReceived  centavos; só é usado quando o pagamento é em dinheiro
     *
     * @throws ValidationException quando uma regra de negócio é violada (HTTP 422)
     */
    public function create(array $items, PaymentMethod $paymentMethod, ?int $amountReceived = null): Sale
    {
        // === DEFESA: VENDA SEM ITENS ===
        // Roda ANTES de qualquer consulta ou gravação. O Form Request também valida
        // isso, mas o service não deve depender dele para impedir uma venda vazia.
        if ($items === []) {
            throw ValidationException::withMessages([
                'items' => 'A venda precisa ter pelo menos um item.',
            ]);
        }

        // === TRANSAÇÃO ===
        // Se qualquer erro acontecer lá dentro (inclusive as ValidationException),
        // o banco desfaz tudo: nunca fica uma venda sem itens ou itens sem venda.
        return DB::transaction(function () use ($items, $paymentMethod, $amountReceived) {

            // === 1. BUSCAR OS PRODUTOS REAIS ===
            // Uma única consulta para todos os itens. keyBy('id') permite achar
            // cada produto pelo id sem consultar o banco de novo.
            $products = Product::whereIn('id', array_column($items, 'product_id'))
                ->get()
                ->keyBy('id');

            // === 2. VALIDAR OS ITENS ===
            // Junta os erros de TODOS os itens antes de lançar a exceção, para o operador
            // ver tudo de uma vez. A chave (items.1.product_id) aponta para a linha
            // do carrinho que o frontend deve destacar.
            $errors = [];
            foreach ($items as $index => $item) {
                $product = $products->get($item['product_id']);

                if ($product === null) {
                    $errors["items.$index.product_id"] = 'Produto não encontrado.';
                } elseif (! $product->available) {
                    $errors["items.$index.product_id"] = "O produto \"{$product->name}\" está indisponível.";
                }
            }

            if ($errors !== []) {
                throw ValidationException::withMessages($errors);
            }

            // === 3. CALCULAR SUBTOTAIS E TOTAL ===
            // Tudo com o preço lido do BANCO, em centavos inteiros. O cliente não manda
            // preço, e se mandasse não seria lido. $lines guarda os dados dos itens
            // (com os snapshots) para gravar no passo 5.
            $total = 0;
            $lines = [];
            foreach ($items as $item) {
                $product = $products->get($item['product_id']);
                $quantity = (int) $item['quantity'];
                $subtotal = $product->price * $quantity; // inteiro x inteiro = inteiro, sem arredondamento

                $total += $subtotal;
                $lines[] = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,   // snapshot do nome
                    'unit_price' => $product->price,    // snapshot do preço
                    'quantity' => $quantity,
                    'subtotal' => $subtotal,
                ];
            }

            // === 4. PAGAMENTO ===
            if ($paymentMethod === PaymentMethod::Cash) {
                // Dinheiro: o valor recebido é obrigatório e precisa cobrir o total
                if ($amountReceived === null) {
                    throw ValidationException::withMessages([
                        'amount_received' => 'Informe o valor recebido.',
                    ]);
                }

                if ($amountReceived < $total) {
                    throw ValidationException::withMessages([
                        'amount_received' => 'O valor recebido é menor que o total da venda.',
                    ]);
                }

                $received = $amountReceived;
                $change = $amountReceived - $total; // troco calculado no backend
            } else {
                // Crédito e débito: qualquer amount_received enviado é IGNORADO.
                // Grava null (não se aplica) e troco 0.
                $received = null;
                $change = 0;
            }

            // === 5. GRAVAR ===
            // payment_method recebe o Enum; o cast de Sale.php o grava como string
            // ('cash', 'credit' ou 'debit'), sem precisar de ->value aqui.
            $sale = Sale::create([
                'total' => $total,
                'payment_method' => $paymentMethod,
                'amount_received' => $received,
                'change_amount' => $change,
            ]);

            // Criando pelo relacionamento, o Laravel preenche o sale_id sozinho
            $sale->items()->createMany($lines);

            // Devolve a venda já com os itens carregados, pronta para o Resource da Etapa 5
            return $sale->load('items');
        });
    }
}