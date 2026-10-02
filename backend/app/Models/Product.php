<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder; // tipo da query, usado no scope de busca
use Illuminate\Database\Eloquent\Factories\HasFactory; // permite Product::factory() nos testes
use Illuminate\Database\Eloquent\Model; // classe base do Eloquent

class Product extends Model
{
    use HasFactory;

    // === MASS ASSIGNMENT ===
    // Lista de campos que podem ser gravados em massa (create/updateOrCreate).
    // Qualquer campo fora da lista é ignorado: proteção contra gravação indevida.
    protected $fillable = ['name', 'code', 'description', 'price', 'available'];

    // === CASTS ===
    protected function casts(): array
    {
        return [
            'price' => 'integer',    // sempre inteiro (centavos), nunca string
            'available' => 'boolean', // 0/1 do banco vira false/true
        ];
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);

        if ($term === '') {
            return $query;
        }

        $escaped = str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $term);
        $like = '%'.$escaped.'%'; // %termo% = "contém"

        return $query->where(function (Builder $q) use ($like) {
            $q->whereRaw("name LIKE ? ESCAPE '!'", [$like])
                ->orWhereRaw("code LIKE ? ESCAPE '!'", [$like]);
        });
    }
}