<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource; // classe base: define o JSON devolvido

/** @mixin \App\Models\Product */
class ProductResource extends JsonResource
{
    // === FORMATO DO PRODUTO NA API ===
    // Lista explícita: só o que o frontend precisa (timestamps ficam de fora).
    // price vai em CENTAVOS (inteiro); a formatação "R$ 18,90" é feita no frontend.
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'description' => $this->description,
            'price' => $this->price,
            'available' => $this->available,
        ];
    }
}
