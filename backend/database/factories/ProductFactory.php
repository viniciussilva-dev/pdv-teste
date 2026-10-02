<?php

namespace Database\Factories;

use App\Models\Product; // usado só na anotação de tipo abaixo (ajuda o VS Code a entender a classe)
use Illuminate\Database\Eloquent\Factories\Factory; // classe base de toda factory do Laravel

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    // === PRODUTO PADRÃO ===
    // Define os campos de um produto gerado por Product::factory()->create().
    // Os valores são aleatórios, mas sempre válidos para a tabela products.
    public function definition(): array
    {
        return [
            // 3 palavras aleatórias, com a primeira letra maiúscula (ex.: "Aut quia sed")
            'name' => ucfirst(fake()->words(3, true)),

            // 13 dígitos começando com 789 (formato de código de barras brasileiro).
            // unique() garante que dois produtos do mesmo teste nunca repitam o código,
            // porque a coluna code tem índice único no banco e repetir causaria erro.
            'code' => fake()->unique()->numerify('789##########'),

            // optional() devolve um texto em parte das vezes e null em outras,
            // já que a descrição é opcional na tabela
            'description' => fake()->optional()->sentence(),

            // Preço em CENTAVOS: de 100 (R$ 1,00) a 20000 (R$ 200,00)
            'price' => fake()->numberBetween(100, 20000),

            // Por padrão o produto é gerado disponível
            'available' => true,
        ];
    }

    // === VARIAÇÃO: PRODUTO INDISPONÍVEL ===
    // "State" é uma variação pronta da factory. Uso nos testes:
    //   Product::factory()->unavailable()->create()
    // O state só sobrescreve o campo informado; os demais seguem o definition().
    public function unavailable(): static
    {
        return $this->state(fn () => ['available' => false]);
    }
}