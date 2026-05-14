<?php
use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Region;
use App\Models\Product;
use App\Services\FeaturedProductService;

new class extends Component {
    use WithPagination;

    public ?int $regionId = null;
    public string $search = '';
    public array $selectedIds = [];   // IDs en orden (define orden de display)

    public string $successMessage = '';
    public string $errorMessage = '';

    const MIN = FeaturedProductService::MIN;
    const MAX = FeaturedProductService::MAX;

    public function mount(): void
    {
        $first = Region::active()->orderBy('name')->first();
        if ($first) {
            $this->regionId = $first->id;
            $this->loadFeatured();
        }
    }

    public function updatedRegionId(): void
    {
        $this->resetMessages();
        $this->resetPage();
        $this->search = '';
        $this->loadFeatured();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    private function loadFeatured(): void
    {
        if (!$this->regionId) {
            $this->selectedIds = [];
            return;
        }

        $region = Region::find($this->regionId);
        if (!$region) {
            $this->selectedIds = [];
            return;
        }

        $this->selectedIds = $region->featuredProducts()
            ->orderByPivot('order')
            ->pluck('products.id')
            ->toArray();
    }

    public function toggle(int $productId): void
    {
        $this->resetMessages();

        if (in_array($productId, $this->selectedIds)) {
            $this->selectedIds = array_values(
                array_filter($this->selectedIds, fn($id) => $id !== $productId)
            );
        } else {
            if (count($this->selectedIds) >= self::MAX) {
                $this->errorMessage = 'Máximo ' . self::MAX . ' productos destacados por región';
                return;
            }
            $this->selectedIds[] = $productId;
        }
    }

    public function moveUp(int $productId): void
    {
        $index = array_search($productId, $this->selectedIds);
        if ($index > 0) {
            [$this->selectedIds[$index - 1], $this->selectedIds[$index]] =
                [$this->selectedIds[$index], $this->selectedIds[$index - 1]];
            $this->selectedIds = array_values($this->selectedIds);
        }
    }

    public function moveDown(int $productId): void
    {
        $index = array_search($productId, $this->selectedIds);
        if ($index !== false && $index < count($this->selectedIds) - 1) {
            [$this->selectedIds[$index + 1], $this->selectedIds[$index]] =
                [$this->selectedIds[$index], $this->selectedIds[$index + 1]];
            $this->selectedIds = array_values($this->selectedIds);
        }
    }

    public function save(FeaturedProductService $service): void
    {
        $this->resetMessages();

        if (!$this->regionId) {
            $this->errorMessage = 'Selecciona una región primero';
            return;
        }

        $region = Region::findOrFail($this->regionId);

        try {
            $service->sync($region, $this->selectedIds);
            $this->successMessage = 'Productos destacados guardados exitosamente';
        } catch (\Exception $e) {
            $this->errorMessage = match ($e->getMessage()) {
                'FEATURED_MIN'              => 'Debes seleccionar al menos ' . self::MIN . ' productos (actualmente: ' . count($this->selectedIds) . ')',
                'FEATURED_MAX'              => 'Máximo ' . self::MAX . ' productos por región',
                'FEATURED_INVALID_PRODUCTS' => 'Uno o más productos seleccionados no son válidos',
                default                     => 'Error al guardar: ' . $e->getMessage(),
            };
        }
    }

    public function with(): array
    {
        $regions = Region::active()->orderBy('name')->get();

        $productsQuery = Product::active()->with(['media', 'category']);

        if ($this->regionId) {
            $productsQuery->where('region_id', $this->regionId);
        }

        if ($this->search) {
            $productsQuery->where('name', 'like', '%' . $this->search . '%');
        }

        $products = $productsQuery->orderBy('name')->paginate(12);

        $selectedProducts = count($this->selectedIds) > 0
            ? Product::with(['media'])->whereIn('id', $this->selectedIds)->get()->keyBy('id')
            : collect();

        $orderedSelected = collect($this->selectedIds)
            ->map(fn($id) => $selectedProducts->get($id))
            ->filter();

        return [
            'regions'         => $regions,
            'products'        => $products,
            'orderedSelected' => $orderedSelected,
            'count'           => count($this->selectedIds),
        ];
    }

    private function resetMessages(): void
    {
        $this->successMessage = '';
        $this->errorMessage = '';
    }

    public function render()
    {
        return $this->view();
    }
};
?>

<div class="container mx-auto px-4 py-8">

    {{-- Header --}}
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900 mb-1">Productos Destacados</h1>
        <p class="text-gray-500 text-sm">Elige entre {{ $this::MIN }} y {{ $this::MAX }} productos por región para mostrar en el ecommerce</p>
    </div>

    {{-- Messages --}}
    @if ($successMessage)
        <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg flex items-center justify-between">
            <span>{{ $successMessage }}</span>
            <button wire:click="$set('successMessage', '')" class="text-green-700 hover:text-green-900">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                </svg>
            </button>
        </div>
    @endif

    @if ($errorMessage)
        <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg flex items-center justify-between">
            <span>{{ $errorMessage }}</span>
            <button wire:click="$set('errorMessage', '')" class="text-red-700 hover:text-red-900">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                </svg>
            </button>
        </div>
    @endif

    {{-- Selector de región --}}
    <div class="mb-6 flex flex-col sm:flex-row gap-4 items-start sm:items-center">
        <label class="text-sm font-medium text-gray-700 whitespace-nowrap">Región:</label>
        <select wire:model.live="regionId"
            class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white min-w-48">
            <option value="">— Selecciona una región —</option>
            @foreach ($regions as $region)
                <option value="{{ $region->id }}">{{ $region->name }}</option>
            @endforeach
        </select>
    </div>

    @if ($regionId)
        <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">

            {{-- Panel izquierdo: catálogo para elegir --}}
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-800">Catálogo de productos</h2>
                    <div class="mt-3">
                        <input type="text" wire:model.live.debounce.300ms="search"
                            placeholder="Buscar producto..."
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm">
                    </div>
                </div>

                <div class="divide-y divide-gray-100">
                    @forelse ($products as $product)
                        @php $isSelected = in_array($product->id, $selectedIds); @endphp
                        <div class="flex items-center gap-3 px-4 py-3 hover:bg-gray-50 transition-colors"
                             wire:key="product-{{ $product->id }}">

                            {{-- Imagen --}}
                            <div class="w-10 h-10 rounded overflow-hidden bg-gray-100 shrink-0">
                                @if ($product->relationLoaded('media') && $product->media->isNotEmpty())
                                    <img src="{{ $product->primaryImage()?->url ?? $product->media->first()->url }}"
                                         class="w-full h-full object-cover" alt="{{ $product->name }}">
                                @else
                                    <div class="w-full h-full flex items-center justify-center">
                                        <svg class="w-5 h-5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                        </svg>
                                    </div>
                                @endif
                            </div>

                            {{-- Info --}}
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 truncate">{{ $product->name }}</p>
                                <p class="text-xs text-gray-500">{{ $product->category?->name ?? '—' }}</p>
                            </div>

                            {{-- Botón toggle --}}
                            <button wire:click="toggle({{ $product->id }})"
                                class="shrink-0 px-3 py-1.5 text-xs font-medium rounded transition-colors
                                    {{ $isSelected
                                        ? 'bg-green-100 text-green-700 hover:bg-red-100 hover:text-red-700'
                                        : 'bg-gray-100 text-gray-600 hover:bg-blue-100 hover:text-blue-700' }}">
                                {{ $isSelected ? 'Quitar' : 'Agregar' }}
                            </button>
                        </div>
                    @empty
                        <div class="px-6 py-8 text-center text-gray-400 text-sm">
                            {{ $search ? 'Sin resultados para "' . $search . '"' : 'No hay productos en esta región' }}
                        </div>
                    @endforelse
                </div>

                @if ($products->hasPages())
                    <div class="px-4 py-3 border-t border-gray-200">
                        {{ $products->links() }}
                    </div>
                @endif
            </div>

            {{-- Panel derecho: destacados seleccionados --}}
            <div class="bg-white rounded-lg shadow flex flex-col">
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h2 class="text-lg font-semibold text-gray-800">Destacados seleccionados</h2>
                        {{-- Badge contador con colores según validación --}}
                        <span class="px-3 py-1 rounded-full text-sm font-bold
                            {{ $count === 0
                                ? 'bg-gray-100 text-gray-500'
                                : ($count < $this::MIN
                                    ? 'bg-yellow-100 text-yellow-700'
                                    : ($count <= $this::MAX
                                        ? 'bg-green-100 text-green-700'
                                        : 'bg-red-100 text-red-700')) }}">
                            {{ $count }} / {{ $this::MAX }}
                        </span>
                    </div>
                    <p class="text-xs text-gray-400 mt-1">
                        @if ($count === 0)
                            Sin selección — agrega al menos {{ $this::MIN }} productos
                        @elseif ($count < $this::MIN)
                            Faltan {{ $this::MIN - $count }} producto(s) para llegar al mínimo
                        @elseif ($count === $this::MAX)
                            Máximo alcanzado
                        @else
                            Puedes agregar {{ $this::MAX - $count }} producto(s) más
                        @endif
                    </p>
                </div>

                <div class="flex-1 divide-y divide-gray-100 overflow-y-auto">
                    @forelse ($orderedSelected as $i => $product)
                        <div class="flex items-center gap-3 px-4 py-3"
                             wire:key="selected-{{ $product->id }}">

                            {{-- Número de orden --}}
                            <span class="w-6 h-6 rounded-full bg-blue-100 text-blue-700 text-xs font-bold flex items-center justify-center shrink-0">
                                {{ $i + 1 }}
                            </span>

                            {{-- Imagen --}}
                            <div class="w-10 h-10 rounded overflow-hidden bg-gray-100 shrink-0">
                                @if ($product->relationLoaded('media') && $product->media->isNotEmpty())
                                    <img src="{{ $product->primaryImage()?->url ?? $product->media->first()->url }}"
                                         class="w-full h-full object-cover" alt="{{ $product->name }}">
                                @else
                                    <div class="w-full h-full flex items-center justify-center">
                                        <svg class="w-5 h-5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                        </svg>
                                    </div>
                                @endif
                            </div>

                            {{-- Nombre --}}
                            <p class="flex-1 text-sm font-medium text-gray-800 truncate">{{ $product->name }}</p>

                            {{-- Controles de orden --}}
                            <div class="flex gap-1 shrink-0">
                                <button wire:click="moveUp({{ $product->id }})"
                                    @if($i === 0) disabled @endif
                                    class="p-1 rounded text-gray-400 hover:text-gray-600 hover:bg-gray-100 disabled:opacity-30 disabled:cursor-not-allowed transition-colors"
                                    title="Subir">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                                    </svg>
                                </button>
                                <button wire:click="moveDown({{ $product->id }})"
                                    @if($i === count($selectedIds) - 1) disabled @endif
                                    class="p-1 rounded text-gray-400 hover:text-gray-600 hover:bg-gray-100 disabled:opacity-30 disabled:cursor-not-allowed transition-colors"
                                    title="Bajar">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </button>
                                <button wire:click="toggle({{ $product->id }})"
                                    class="p-1 rounded text-red-400 hover:text-red-600 hover:bg-red-50 transition-colors"
                                    title="Quitar">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    @empty
                        <div class="px-6 py-10 text-center text-gray-400 text-sm">
                            Aún no has seleccionado productos
                        </div>
                    @endforelse
                </div>

                {{-- Footer con botón guardar --}}
                <div class="px-6 py-4 border-t border-gray-200 bg-gray-50 rounded-b-lg">
                    <button
                        wire:click="save"
                        wire:loading.attr="disabled"
                        @if ($count > 0 && $count < $this::MIN) disabled title="Selecciona al menos {{ $this::MIN }} productos" @endif
                        class="w-full px-4 py-2.5 rounded-lg text-sm font-semibold transition-colors
                            {{ ($count === 0 || ($count >= $this::MIN && $count <= $this::MAX))
                                ? 'bg-blue-600 hover:bg-blue-700 text-white'
                                : 'bg-gray-300 text-gray-500 cursor-not-allowed' }}">
                        <span wire:loading.remove wire:target="save">
                            @if ($count === 0) Limpiar destacados @else Guardar destacados @endif
                        </span>
                        <span wire:loading wire:target="save">Guardando...</span>
                    </button>

                    @if ($count > 0 && $count < $this::MIN)
                        <p class="text-xs text-yellow-600 text-center mt-2">
                            Selecciona {{ $this::MIN - $count }} producto(s) más para poder guardar
                        </p>
                    @endif
                </div>
            </div>

        </div>
    @else
        <div class="bg-white rounded-lg shadow px-6 py-12 text-center text-gray-400">
            Selecciona una región para gestionar sus productos destacados
        </div>
    @endif
</div>
