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
            'name'       => $request->name,
            'color'      => $request->color ?? '#3B82F6',
            'price_cents'=> $request->price_cents,
            'polygon'    => $request->polygon,
            'sort_order' => $request->sort_order ?? 0,
            'active'     => $request->boolean('active', true),
        ]);

        return response()->json($zone, 201);
    }

    public function show(DeliveryZone $deliveryZone): JsonResponse
    {
        return response()->json($deliveryZone);
    }

    public function update(Request $request, DeliveryZone $deliveryZone): JsonResponse
    {
        $validated = $request->validate([
            'name'       => 'sometimes|string|max:100',
            'color'      => 'sometimes|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'price_cents'=> 'sometimes|integer|min:0',
            'polygon'    => 'sometimes|array',
            'sort_order' => 'sometimes|integer|min:0',
            'active'     => 'sometimes|boolean',
        ]);

        $zone = $this->service->update($deliveryZone, $validated);

        return response()->json($zone);
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
            'lat'     => 'required_without:address|numeric|between:-90,90',
            'lng'     => 'required_without:address|numeric|between:-180,180',
        ]);

        $lat = $request->lat;
        $lng = $request->lng;

        if ($request->filled('address') && !($lat && $lng)) {
            $coords = $this->service->geocodeAddress($request->address);

            if (!$coords) {
                return response()->json([
                    'zone'   => null,
                    'message'=> 'No se pudo geocodificar la dirección',
                ]);
            }

            $lat = $coords['lat'];
            $lng = $coords['lng'];
        }

        $zone = $this->service->detectZone((float) $lat, (float) $lng);

        if (!$zone) {
            return response()->json([
                'zone'        => null,
                'price_cents' => 0,
                'message'     => 'La dirección está fuera de las zonas de cobertura',
            ]);
        }

        return response()->json([
            'zone'        => $zone,
            'price_cents' => $zone->price_cents,
            'lat'         => $lat,
            'lng'         => $lng,
        ]);
    }
}
