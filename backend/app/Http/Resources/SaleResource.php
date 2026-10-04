<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Sale */
class SaleResource extends JsonResource
{
    // === COMPROVANTE DA VENDA ===
    // Valores em centavos. created_at vai em ISO 8601 (o frontend formata a data local).
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id, // identificador usado para consultar o comprovante
            'total' => $this->total,
            'payment_method' => $this->payment_method->value, // 'cash', 'credit' ou 'debit'
            'amount_received' => $this->amount_received, // null em cartão
            'change_amount' => $this->change_amount,
            'created_at' => $this->created_at->toIso8601String(),
            // whenLoaded: inclui os itens só se já foram carregados (evita consulta extra)
            'items' => SaleItemResource::collection($this->whenLoaded('items')),
        ];
    }
}
