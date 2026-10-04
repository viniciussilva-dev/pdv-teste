<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model; // classe base do Eloquent
use Illuminate\Database\Eloquent\Relations\BelongsTo; // tipo de retorno dos relacionamentos

class SaleItem extends Model
{
    // === MASS ASSIGNMENT ===
    // Note que sale_id NÃO está na lista: o SaleService criará os itens pelo
    // relacionamento ($sale->items()->create(...)), e o Laravel preenche o sale_id
    // sozinho. Menos campos graváveis em massa = menos superfície para erro.
    protected $fillable = ['product_id', 'product_name', 'unit_price', 'quantity', 'subtotal'];

    // === CASTS ===
    // Valores monetários e quantidade sempre como inteiros
    protected function casts(): array
    {
        return [
            'unit_price' => 'integer', // snapshot do preço, em centavos
            'quantity' => 'integer',
            'subtotal' => 'integer',   // centavos
        ];
    }

    // === RELACIONAMENTOS ===
    // O item pertence a uma venda. Uso: $item->sale
    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    // O item aponta para o produto do catálogo. Uso: $item->product
    // Atenção: para exibir o comprovante use product_name e unit_price (snapshot),
    // NUNCA o produto atual, que pode ter mudado de nome ou preço depois da venda.
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}