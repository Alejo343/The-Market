<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTaxRequest;
use App\Http\Requests\UpdateTaxRequest;
use App\Http\Resources\TaxResource;
use App\Models\Tax;
use App\Services\TaxService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Exception;

class TaxController extends Controller
{
    public function __construct(
        protected TaxService $service
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $taxes = $this->service->list(
            activeOnly: $request->boolean('active_only'),
            search: $request->filled('search')
                ? $request->input('search')
                : null
        );

        return TaxResource::collection($taxes);
    }

    public function store(StoreTaxRequest $request): JsonResponse
    {
        $tax = $this->service->create(
            $request->validated()
        );

        return (new TaxResource($tax))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Tax $tax): TaxResource
    {
        return new TaxResource(
            $this->service->show($tax)
        );
    }

    public function update(UpdateTaxRequest $request, Tax $tax): TaxResource
    {
        return new TaxResource(
            $this->service->update(
                $tax,
                $request->validated()
            )
        );
    }

    public function destroy(Tax $tax): JsonResponse
    {
        try {
            $this->service->delete($tax);

            return response()->json([
                'message' => 'Impuesto eliminado exitosamente'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => match ($e->getMessage()) {
                    'TAX_IN_USE' =>
                    'No se puede eliminar un impuesto que está siendo usado por variantes de productos',
                    default => 'Error inesperado'
                }
            ], 422);
        }
    }
}
