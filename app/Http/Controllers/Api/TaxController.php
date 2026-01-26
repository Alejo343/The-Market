<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTaxRequest;
use App\Http\Requests\UpdateTaxRequest;
use App\Http\Resources\TaxResource;
use App\Models\Tax;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TaxController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Tax::query();

        // Filtrar solo activos
        if ($request->boolean('active_only')) {
            $query->active();
        }

        // Búsqueda por nombre
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->input('search') . '%');
        }

        $taxes = $query->orderBy('percentage')->get();

        return TaxResource::collection($taxes);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTaxRequest $request): JsonResponse
    {
        $tax = Tax::create($request->validated());

        return (new TaxResource($tax))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Tax $tax): TaxResource
    {
        return new TaxResource($tax);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTaxRequest $request, Tax $tax): TaxResource
    {
        $tax->update($request->validated());

        return new TaxResource($tax);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Tax $tax): JsonResponse
    {
        // Verificar si tiene variantes asociadas
        if ($tax->productVariants()->exists()) {
            return response()->json([
                'message' => 'No se puede eliminar un impuesto que está siendo usado por variantes de productos',
                'error' => 'tax_in_use'
            ], 422);
        }

        $tax->delete();

        return response()->json([
            'message' => 'Impuesto eliminado exitosamente'
        ], 200);
    }
}
