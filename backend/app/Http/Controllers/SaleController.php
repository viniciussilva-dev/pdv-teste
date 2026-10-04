<?php

namespace App\Http\Controllers;

use App\Enums\PaymentMethod;
use App\Http\Requests\StoreSaleRequest;
use App\Http\Resources\SaleResource;
use App\Models\Sale;
use App\Services\SaleService;
use Illuminate\Http\JsonResponse;

class SaleController extends Controller
{
    // POST /api/sales
    // O controller só recebe, delega e responde: as regras de negócio ficam no SaleService.
    public function store(StoreSaleRequest $request, SaleService $service): JsonResponse
    {
        // validated() traz apenas os campos validados: qualquer "price", "total" ou
        // "subtotal" enviado pelo cliente já foi descartado aqui
        $data = $request->validated();

        $sale = $service->create(
            $data['items'],
            PaymentMethod::from($data['payment_method']),
            isset($data['amount_received']) ? (int) $data['amount_received'] : null,
        );

        // 201 Created, com o comprovante da venda
        return (new SaleResource($sale))->response()->setStatusCode(201);
    }

    // GET /api/sales/{id}  (id inexistente vira 404 pelo route model binding)
    public function show(Sale $sale): SaleResource
    {
        return new SaleResource($sale->load('items'));
    }
}
