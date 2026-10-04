<?php

namespace App\Http\Requests;

use App\Enums\PaymentMethod; // fonte única dos valores válidos de pagamento
use Illuminate\Foundation\Http\FormRequest; // classe base de validação de requisições
use Illuminate\Validation\Rule; // regras montadas por código (Rule::in)

class StoreSaleRequest extends FormRequest
{
    // Sem login neste projeto: qualquer requisição pode tentar registrar uma venda
    public function authorize(): bool
    {
        return true;
    }

    // === REGRAS: validam só a FORMA do payload ===
    // O que depende do banco (produto existe/disponível) ou do dinheiro (valor cobre o total)
    // é validado no SaleService. Preços NÃO são validados porque NÃO são lidos:
    // validated() devolve apenas os campos listados aqui.
    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1', 'max:100'],
            'items.*.product_id' => ['required', 'integer', 'distinct'], // distinct: produto repetido é recusado
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:999'],

            'payment_method' => ['required', Rule::in(array_map(fn (PaymentMethod $m) => $m->value, PaymentMethod::cases()))],

            // exclude_unless: em crédito/débito o campo é REMOVIDO dos dados validados
            // (ignorado, sem gerar erro), igual ao comportamento do SaleService.
            // Em dinheiro é obrigatório, inteiro (centavos) e dentro de um limite razoável.
            'amount_received' => ['exclude_unless:payment_method,cash', 'required', 'integer', 'min:0', 'max:100000000'],
        ];
    }

    // === MENSAGENS EM PORTUGUÊS ===
    public function messages(): array
    {
        return [
            'items.required' => 'A venda precisa ter pelo menos um item.',
            'items.array' => 'Os itens da venda são inválidos.',
            'items.min' => 'A venda precisa ter pelo menos um item.',
            'items.max' => 'A venda pode ter no máximo 100 itens.',

            'items.*.product_id.required' => 'Informe o produto.',
            'items.*.product_id.integer' => 'Produto inválido.',
            'items.*.product_id.distinct' => 'Este produto aparece mais de uma vez na venda.',

            'items.*.quantity.required' => 'Informe a quantidade.',
            'items.*.quantity.integer' => 'A quantidade deve ser um número inteiro.',
            'items.*.quantity.min' => 'A quantidade deve ser de pelo menos 1.',
            'items.*.quantity.max' => 'A quantidade máxima por item é 999.',

            'payment_method.required' => 'Informe a forma de pagamento.',
            'payment_method.in' => 'Forma de pagamento inválida.',

            'amount_received.required' => 'Informe o valor recebido.',
            'amount_received.integer' => 'O valor recebido deve ser um número inteiro, em centavos.',
            'amount_received.min' => 'O valor recebido não pode ser negativo.',
            'amount_received.max' => 'O valor recebido está acima do limite permitido.',
        ];
    }
}
