<?php

use App\Http\Controllers\ProductController;
use App\Http\Controllers\SaleController;
use Illuminate\Support\Facades\Route;

// === ROTAS DA API (prefixo /api, definido em bootstrap/app.php) ===

// Produtos: somente leitura
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{product}', [ProductController::class, 'show']);

// Vendas: somente criar e consultar.
// NÃO existem rotas de edição nem de exclusão: uma venda finalizada não muda.
Route::post('/sales', [SaleController::class, 'store']);
Route::get('/sales/{sale}', [SaleController::class, 'show']);
