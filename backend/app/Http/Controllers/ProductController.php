<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductController extends Controller
{
    // GET /api/products?search=cafe
    // Lista os produtos (inclusive os indisponíveis, marcados com available=false).
    // Sem paginação: o catálogo do teste é pequeno.
    public function index(Request $request): AnonymousResourceCollection
    {
        // Valida o parâmetro: evita ?search[]=x (array) e termos gigantes
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        // O scope search() filtra por nome OU código, com escape dos curingas do LIKE
        $products = Product::search($filters['search'] ?? null)->orderBy('id')->get();

        return ProductResource::collection($products);
    }

    // GET /api/products/{id}  (id inexistente vira 404 pelo route model binding)
    public function show(Product $product): ProductResource
    {
        return new ProductResource($product);
    }
}
