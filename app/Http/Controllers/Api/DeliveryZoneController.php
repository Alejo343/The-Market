<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDeliveryZoneRequest;
use App\Models\DeliveryZone;
use App\Services\DeliveryZoneService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeliveryZoneController extends Controller
{
    public function __construct(protected DeliveryZoneService $service) {}

    public function index(): JsonResponse
    {
        return response()->json($this->service->allIncludingInactive());
    }

    public function store(StoreDeliveryZoneRequest $request): JsonResponse
    {
        $zone = $this->service->store([
            'name' => $request->name,
            'color' => $request->color ?? '#3B82F6',
            'price_cents' => $request->price_cents ?? 0,
            'polygon' => $request->polygon,
            'sort_order' => $request->sort_order ?? 0,
            'active' => $request->boolean('active', true),
            'product_variant_id' => $request->product_variant_id,
        ]);

        return response()->json($zone->load('variant.product'), 201);
    }

    public function show(DeliveryZone $deliveryZone): JsonResponse
    {
        return response()->json($deliveryZone);
    }

    public function update(Request $request, DeliveryZone $deliveryZone): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:100',
            'color' => 'sometimes|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'price_cents' => 'sometimes|integer|min:0',
            'polygon' => 'sometimes|array',
            'sort_order' => 'sometimes|integer|min:0',
            'active' => 'sometimes|boolean',
            'product_variant_id' => [
                'sometimes', 'nullable',
                \Illuminate\Validation\Rule::exists('product_variants', 'id')
                    ->where(fn ($q) => $q->where('sku', 'like', 'DOM%')),
            ],
        ]);

        $zone = $this->service->update($deliveryZone, $validated);

        return response()->json($zone->load('variant.product'));
    }

    public function destroy(DeliveryZone $deliveryZone): JsonResponse
    {
        $this->service->delete($deliveryZone);

        return response()->json(['message' => 'Zona eliminada']);
    }

    /**
     * Detecta la zona de entrega para una dirección o coordenadas dadas.
     * Body: { address: string } o { lat: float, lng: float }
     */
    public function detect(Request $request): JsonResponse
    {
        $request->validate([
            'address' => 'required_without_all:lat,lng|string',
            'lat' => 'required_without:address|numeric|between:-90,90',
            'lng' => 'required_without:address|numeric|between:-180,180',
        ]);

        $lat = $request->lat;
        $lng = $request->lng;

        if ($request->filled('address') && ! ($lat && $lng)) {
            $coords = $this->service->geocodeAddress($request->address);

            if (! $coords) {
                return response()->json([
                    'zone' => null,
                    'message' => 'No se pudo geocodificar la dirección',
                ]);
            }

            $lat = $coords['lat'];
            $lng = $coords['lng'];
        }

        $zone = $this->service->detectZone((float) $lat, (float) $lng);

        if (! $zone) {
            return response()->json([
                'zone' => null,
                'price_cents' => 0,
                'product_variant_id' => null,
                'message' => 'La dirección está fuera de las zonas de cobertura',
            ]);
        }

        $priceCents = $zone->variant
            ? (int) round((float) $zone->variant->price * 100)
            : $zone->price_cents;

        return response()->json([
            'zone' => $zone,
            'price_cents' => $priceCents,
            'product_variant_id' => $zone->product_variant_id,
            'lat' => $lat,
            'lng' => $lng,
        ]);
    }
}
