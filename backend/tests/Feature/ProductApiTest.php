<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase; // banco isolado (SQLite em memória)
use Tests\TestCase;

// === API DE PRODUTOS ===
class ProductApiTest extends TestCase
{
    use RefreshDatabase;

    // Lista (com indisponíveis) e busca por nome, por código, sem resultado e com curinga
    public function test_lists_products_including_unavailable_and_searches_by_name_or_code(): void
    {
        Product::factory()->create(['name' => 'Café Torrado', 'code' => '7890000000011', 'price' => 1890]);
        Product::factory()->create(['name' => 'Açúcar Refinado', 'code' => '7890000000028']);
        Product::factory()->unavailable()->create(['name' => 'Queijo Mussarela', 'code' => '7890000000035']);

        $this->getJson('/api/products')
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure(['data' => [['id', 'name', 'code', 'description', 'price', 'available']]]);

        // Indisponível aparece, marcado com available = false
        $this->getJson('/api/products?search=Queijo')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.available', false);

        // Por nome (parcial) e por código
        $this->getJson('/api/products?search=Torrado')->assertJsonPath('data.0.price', 1890);
        $this->getJson('/api/products?search=7890000000028')->assertJsonPath('data.0.name', 'Açúcar Refinado');

        // Sem resultado; e um "%" digitado é tratado como texto, não como curinga
        $this->getJson('/api/products?search=inexistente')->assertOk()->assertJsonCount(0, 'data');
        $this->getJson('/api/products?search=%25')->assertOk()->assertJsonCount(0, 'data');
    }

    // Detalhe de um produto e 404 para id inexistente
    public function test_shows_one_product_and_returns_404_for_unknown_id(): void
    {
        $product = Product::factory()->create(['price' => 1890]);

        $this->getJson("/api/products/{$product->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $product->id)
            ->assertJsonPath('data.price', 1890);

        $this->getJson('/api/products/999999')->assertNotFound();
    }
}
