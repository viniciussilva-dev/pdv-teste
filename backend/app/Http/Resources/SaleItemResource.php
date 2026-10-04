<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\SaleItem */
class SaleItemResource extends JsonResource
{
    // === ITEM DO COMPROVANTE ===
    // Usa SOMENTE os snapshots gravados na venda (product_name e unit_price),
    // nunca o produto atual do catálogo, que pode ter mudado depois.
    public function toArray(Request $request): array
    {
        return [
            'product_id' => $this->product_id,
            'product_name' => $this->product_name,
            'unit_price' => $this->unit_price,
            'quantity' => $this->quantity,
            'subtotal' => $this->subtotal,
        ];
    }
}
