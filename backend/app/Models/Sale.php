<?php

namespace App\Models;

use App\Enums\PaymentMethod; // tipo usado no cast do método de pagamento
use Illuminate\Database\Eloquent\Model; // classe base do Eloquent
use Illuminate\Database\Eloquent\Relations\HasMany; // tipo de retorno do relacionamento items()

class Sale extends Model
{
    // === MASS ASSIGNMENT ===
    // Campos que o SaleService poderá gravar de uma vez. O que não está na lista
    // é ignorado em create(): proteção contra gravação indevida de campos.
    protected $fillable = ['total', 'payment_method', 'amount_received', 'change_amount'];

    // === CASTS ===
    // Converte os valores do banco para os tipos PHP corretos ao ler a venda
    protected function casts(): array
    {
        return [
            'total' => 'integer',                 // centavos, sempre inteiro
            'payment_method' => PaymentMethod::class, // texto 'cash' do banco vira o Enum
            'amount_received' => 'integer',       // centavos; continua null em cartão
            'change_amount' => 'integer',         // centavos
        ];
    }

    // === RELACIONAMENTOS ===
    // Uma venda tem vários itens (sale_items.sale_id aponta para esta venda).
    // Uso: $sale->items
    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }
}