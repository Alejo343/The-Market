<?php
use Livewire\Component;
use App\Models\DeliveryZone;
use App\Services\DeliveryZoneService;

new class extends Component {
    public string $name        = '';
    public string $color       = '#3B82F6';
    public int    $price       = 0;   // pesos — se convierte a centavos al guardar
    public int    $sort_order  = 0;
    public array  $polygon     = [];
    public bool   $active      = true;

    public ?int   $editingId   = null;
    public ?int   $deletingId  = null;

    public string $successMessage = '';
    public string $errorMessage   = '';

    public function with(DeliveryZoneService $service): array
    {
        return ['zones' => $service->allIncludingInactive()];
    }

    public function save(DeliveryZoneService $service): void
    {
        $this->resetMessages();

        $this->validate([
            'name'    => 'required|string|max:100',
            'color'   => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'price'   => 'required|integer|min:0',
            'polygon' => 'required|array',
        ], [
            'name.required'    => 'El nombre de la zona es obligatorio',
            'polygon.required' => 'Debes dibujar el polígono de la zona en el mapa',
            'price.min'        => 'El costo de envío no puede ser negativo',
        ]);

        try {
            $data = [
                'name'        => $this->name,
                'color'       => $this->color,
                'price_cents' => $this->price * 100,
                'polygon'     => $this->polygon,
                'sort_order'  => $this->sort_order,
                'active'      => $this->active,
            ];

            if ($this->editingId) {
                $zone = DeliveryZone::findOrFail($this->editingId);
                $service->update($zone, $data);
                $this->successMessage = "Zona '{$this->name}' actualizada";
            } else {
                $data['sort_order'] = DeliveryZone::max('sort_order') + 1;
                $service->store($data);
                $this->successMessage = "Zona '{$this->name}' creada";
            }

            $this->resetForm();
            $this->dispatch('zones-updated');
        } catch (\Exception $e) {
            $this->errorMessage = 'Error al guardar: ' . $e->getMessage();
        }
    }

    public function editZone(int $id): void
    {
        $this->resetMessages();
        $this->editingId = $id;
        $zone = DeliveryZone::findOrFail($id);

        $this->name       = $zone->name;
        $this->color      = $zone->color;
        $this->price      = intdiv($zone->price_cents, 100);
        $this->sort_order = $zone->sort_order;
        $this->active     = $zone->active;
        $this->polygon    = $zone->polygon ?? [];

        $this->dispatch('load-polygon', polygon: $zone->polygon, color: $zone->color);
    }

    public function confirmDelete(int $id): void
    {
        $this->resetMessages();
        $this->deletingId = $id;
    }

    public function cancelDelete(): void
    {
        $this->deletingId = null;
    }

    public function deleteZone(DeliveryZoneService $service): void
    {
        try {
            $zone = DeliveryZone::findOrFail($this->deletingId);
            $service->delete($zone);
            $this->successMessage = "Zona '{$zone->name}' eliminada";
            $this->deletingId = null;
            $this->dispatch('zones-updated');
        } catch (\Exception $e) {
            $this->errorMessage = 'Error al eliminar: ' . $e->getMessage();
            $this->deletingId = null;
        }
    }

    public function cancelEdit(): void
    {
        $this->resetForm();
        $this->dispatch('clear-drawing');
    }

    private function resetForm(): void
    {
        $this->name       = '';
        $this->color      = '#3B82F6';
        $this->price      = 0;
        $this->sort_order = 0;
        $this->polygon    = [];
        $this->active     = true;
        $this->editingId  = null;
    }

    private function resetMessages(): void
    {
        $this->successMessage = '';
        $this->errorMessage   = '';
    }

    public function render()
    {
        return $this->view();
    }
};
?>

<div class="flex h-[calc(100vh-8rem)] gap-0 overflow-hidden rounded-xl shadow-lg">

    {{-- ── Panel izquierdo ── --}}
    <div class="w-80 flex-shrink-0 bg-white flex flex-col border-r border-gray-200">

        {{-- Header --}}
        <div class="px-5 py-4 border-b border-gray-100">
            <h2 class="text-lg font-bold text-gray-900">Zonas de Envío</h2>
            <p class="text-xs text-gray-500 mt-0.5">Dibuja polígonos en el mapa para definir zonas</p>
        </div>

        {{-- Messages --}}
        @if ($successMessage)
            <div class="mx-4 mt-3 p-3 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm flex justify-between">
                {{ $successMessage }}
                <button wire:click="$set('successMessage','')" class="ml-2 text-green-500 hover:text-green-700">&times;</button>
            </div>
        @endif
        @if ($errorMessage)
            <div class="mx-4 mt-3 p-3 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm flex justify-between">
                {{ $errorMessage }}
                <button wire:click="$set('errorMessage','')" class="ml-2 text-red-500 hover:text-red-700">&times;</button>
            </div>
        @endif

        {{-- Zona actual en edición --}}
        <div class="px-4 py-4 border-b border-gray-100">
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">
                {{ $editingId ? 'Editando zona' : 'Nueva zona' }}
            </p>

            <div class="space-y-3">
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Nombre</label>
                    <input type="text" wire:model="name" placeholder="Ej: Zona Centro"
                        class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    @error('name') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </div>

                <div class="flex gap-3">
                    <div class="flex-1">
                        <label class="block text-xs font-medium text-gray-700 mb-1">Costo envío (pesos)</label>
                        <input type="number" wire:model="price" min="0" step="100"
                            class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        @error('price') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Color</label>
                        <input type="color" wire:model="color"
                            class="w-10 h-9 rounded cursor-pointer border border-gray-300">
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <input type="checkbox" wire:model="active" id="zone-active"
                        class="w-4 h-4 text-blue-600 border-gray-300 rounded">
                    <label for="zone-active" class="text-sm text-gray-700">Zona activa</label>
                </div>

                @if (empty($polygon))
                    <p class="text-xs text-amber-600 bg-amber-50 px-3 py-2 rounded-lg">
                        Dibuja el polígono de la zona en el mapa
                    </p>
                    @error('polygon') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                @else
                    <p class="text-xs text-green-600 bg-green-50 px-3 py-2 rounded-lg">
                        ✓ Polígono capturado correctamente
                    </p>
                @endif

                <div class="flex gap-2">
                    <button wire:click="save" wire:loading.attr="disabled"
                        class="flex-1 px-3 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50">
                        {{ $editingId ? 'Actualizar' : 'Guardar zona' }}
                    </button>
                    @if ($editingId)
                        <button wire:click="cancelEdit"
                            class="px-3 py-2 bg-gray-200 text-gray-700 text-sm rounded-lg hover:bg-gray-300 transition-colors">
                            Cancelar
                        </button>
                    @endif
                </div>
            </div>
        </div>

        {{-- Lista de zonas --}}
        <div class="flex-1 overflow-y-auto px-4 py-3 space-y-2">
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Zonas guardadas</p>

            @forelse($zones as $zone)
                <div wire:key="zone-{{ $zone->id }}"
                    class="flex items-center justify-between p-3 bg-gray-50 rounded-lg border border-gray-100 hover:border-gray-200 transition-colors">
                    <div class="flex items-center gap-2 min-w-0">
                        <span class="w-3 h-3 rounded-full flex-shrink-0"
                            style="background-color: {{ $zone->color }}"></span>
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-gray-900 truncate">{{ $zone->name }}</p>
                            <p class="text-xs text-gray-500">
                                ${{ number_format($zone->price_cents / 100, 0, ',', '.') }}
                                · {{ $zone->active ? 'activa' : 'inactiva' }}
                            </p>
                        </div>
                    </div>
                    <div class="flex gap-1 flex-shrink-0">
                        <button wire:click="editZone({{ $zone->id }})"
                            class="p-1.5 text-blue-600 hover:bg-blue-50 rounded transition-colors" title="Editar">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                        </button>
                        <button wire:click="confirmDelete({{ $zone->id }})"
                            class="p-1.5 text-red-500 hover:bg-red-50 rounded transition-colors" title="Eliminar">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </button>
                    </div>
                </div>
            @empty
                <p class="text-sm text-gray-400 text-center py-4">No hay zonas creadas aún</p>
            @endforelse
        </div>
    </div>

    {{-- ── Mapa Leaflet — wire:ignore impide que Livewire destruya el mapa al re-renderizar ── --}}
    <div class="flex-1 relative" wire:ignore
        x-data="deliveryZoneMap()"
        @zones-updated.window="reloadZones()"
        @load-polygon.window="loadPolygon($event.detail.polygon, $event.detail.color)"
        @clear-drawing.window="clearDrawing()">

        <div id="delivery-map" class="w-full h-full"
            data-lat="{{ env('STORE_LAT', '4.7109886') }}"
            data-lng="{{ env('STORE_LNG', '-74.072092') }}"></div>

        {{-- Instrucciones flotantes --}}
        <div class="absolute top-4 right-4 z-[1000] bg-white rounded-lg shadow-lg p-3 text-xs text-gray-600 max-w-52 pointer-events-none">
            <p class="font-semibold text-gray-800 mb-2">Cómo dibujar una zona</p>
            <ol class="space-y-1.5 list-decimal list-inside">
                <li>En la barra izquierda del mapa, haz clic en el ícono de <strong>polígono</strong> o <strong>rectángulo</strong></li>
                <li>Haz clic en el mapa para trazar los vértices de la zona</li>
                <li>Cierra el polígono haciendo clic en el primer punto</li>
                <li>Completa nombre y precio en el panel izquierdo y guarda</li>
            </ol>
            <p class="mt-2 text-amber-600 font-medium">La zona 2 en adelante se recorta automáticamente.</p>
        </div>
    </div>

    {{-- Modal eliminar --}}
    @if ($deletingId)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[2000]">
            <div class="bg-white rounded-xl p-6 max-w-sm w-full mx-4 shadow-xl">
                <h3 class="text-lg font-semibold text-gray-900 mb-3">Eliminar zona</h3>
                <p class="text-gray-600 mb-5 text-sm">¿Confirmas que deseas eliminar esta zona de envío?</p>
                <div class="flex justify-end gap-3">
                    <button wire:click="cancelDelete"
                        class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 text-sm">
                        Cancelar
                    </button>
                    <button wire:click="deleteZone"
                        class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 text-sm">
                        Eliminar
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>

