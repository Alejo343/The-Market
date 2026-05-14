<?php
use Livewire\Component;
use App\Services\RegionService;
use App\Models\Region;

new class extends Component {
    public string $search = '';
    public bool $showActiveOnly = false;
    public ?int $editingId = null;
    public ?int $deletingId = null;

    // Form fields para edición
    public string $name = '';
    public string $description = '';
    public bool $active = true;
    public ?int $parentId = null;

    // Asignación rápida de jerarquía
    public ?int $assignChildId = null;
    public ?int $assignParentId = null;

    public string $errorMessage = '';
    public string $successMessage = '';

    public function mount()
    {
        $this->resetMessages();
    }

    /**
     * Computed property para obtener las regiones filtradas
     */
    public function with(RegionService $regionService): array
    {
        try {
            $regions = $regionService->list(activeOnly: $this->showActiveOnly, search: $this->search);

            $regions->loadCount(['products']);
            $regions->load('parent');

            $allRegions = Region::orderBy('name')->get();

            $parentRegions = $allRegions->whereNull('parent_id')->values();

            return [
                'regions' => $regions,
                'allRegions' => $allRegions,
                'parentRegions' => $parentRegions,
            ];
        } catch (\Exception $e) {
            \Log::error('Error in with(): ' . $e->getMessage());
            $this->errorMessage = $this->translateError($e->getMessage());
            return [
                'regions' => collect(),
                'allRegions' => collect(),
                'parentRegions' => collect(),
            ];
        }
    }

    /**
     * Actualiza el filtro de búsqueda
     */
    public function updatedSearch()
    {
        $this->resetMessages();
    }

    public function updatedShowActiveOnly()
    {
        $this->resetMessages();
    }

    /**
     * Inicia el modo de edición
     */
    public function edit(int $id)
    {
        $this->resetMessages();
        $this->editingId = $id;

        try {
            $region = Region::findOrFail($id);
            $this->name = $region->name;
            $this->description = $region->description ?? '';
            $this->active = $region->active;
            $this->parentId = $region->parent_id;
        } catch (\Exception $e) {
            $this->errorMessage = 'Error al cargar la región';
            $this->cancelEdit();
        }
    }

    /**
     * Cancela la edición
     */
    public function cancelEdit()
    {
        $this->editingId = null;
        $this->name = '';
        $this->description = '';
        $this->active = true;
        $this->parentId = null;
    }

    /**
     * Guarda la región editada
     */
    public function save(RegionService $regionService)
    {
        $this->resetMessages();

        $this->validate(
            [
                'name' => 'required|string|max:255',
                'description' => 'nullable|string|max:500',
                'active' => 'boolean',
            ],
            [
                'name.required' => 'El nombre es obligatorio',
                'name.max' => 'El nombre no puede exceder 255 caracteres',
                'description.max' => 'La descripción no puede exceder 500 caracteres',
            ],
        );

        try {
            $region = Region::findOrFail($this->editingId);

            $regionService->update($region, [
                'name' => $this->name,
                'description' => $this->description ?: null,
                'active' => $this->active,
                'parent_id' => $this->parentId,
            ]);

            $this->successMessage = 'Región actualizada exitosamente';
            $this->cancelEdit();
        } catch (\Exception $e) {
            $this->errorMessage = $this->translateError($e->getMessage());
        }
    }

    /**
     * Confirma la eliminación
     */
    public function confirmDelete(int $id)
    {
        $this->resetMessages();
        $this->deletingId = $id;
    }

    /**
     * Cancela la eliminación
     */
    public function cancelDelete()
    {
        $this->deletingId = null;
    }

    /**
     * Elimina la región
     */
    public function delete(RegionService $regionService)
    {
        $this->resetMessages();

        try {
            $region = Region::findOrFail($this->deletingId);
            $regionService->delete($region);

            $this->successMessage = 'Región eliminada exitosamente';
            $this->deletingId = null;
        } catch (\Exception $e) {
            $this->errorMessage = $this->translateError($e->getMessage());
            $this->deletingId = null;
        }
    }

    /**
     * Asignación rápida de región padre desde la sección de jerarquía
     */
    public function assignParent(RegionService $regionService)
    {
        $this->resetMessages();

        if (! $this->assignChildId) {
            $this->errorMessage = 'Selecciona una región para asignar';
            return;
        }

        if ($this->assignChildId === $this->assignParentId) {
            $this->errorMessage = 'Una región no puede ser su propio padre';
            return;
        }

        try {
            $region = Region::findOrFail($this->assignChildId);
            $regionService->update($region, ['parent_id' => $this->assignParentId]);

            $this->successMessage = 'Jerarquía actualizada exitosamente';
            $this->assignChildId = null;
            $this->assignParentId = null;
        } catch (\Exception $e) {
            $this->errorMessage = $this->translateError($e->getMessage());
        }
    }

    /**
     * Resetea los mensajes de error y éxito
     */
    private function resetMessages()
    {
        $this->errorMessage = '';
        $this->successMessage = '';
    }

    /**
     * Traduce los mensajes de error al español
     */
    private function translateError(string $error): string
    {
        return match ($error) {
            'REGION_HAS_PRODUCTS' => 'No se puede eliminar: la región tiene productos asociados',
            default => 'Error: ' . $error,
        };
    }

    public function render()
    {
        return $this->view();
    }
};
?>

<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Regiones</h1>
        <p class="text-gray-600">Gestiona las regiones o ubicaciones de productos</p>
    </div>

    <!-- Messages -->
    @if ($successMessage)
        <div
            class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg flex items-center justify-between">
            <span>{{ $successMessage }}</span>
            <button wire:click="$set('successMessage', '')" class="text-green-700 hover:text-green-900">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                        d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                        clip-rule="evenodd"></path>
                </svg>
            </button>
        </div>
    @endif

    @if ($errorMessage)
        <div
            class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg flex items-center justify-between">
            <span>{{ $errorMessage }}</span>
            <button wire:click="$set('errorMessage', '')" class="text-red-700 hover:text-red-900">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                        d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                        clip-rule="evenodd"></path>
                </svg>
            </button>
        </div>
    @endif

    <!-- Filters and Actions -->
    <div class="mb-6 flex flex-col sm:flex-row gap-4 items-start sm:items-center justify-between">
        <div class="flex flex-col sm:flex-row gap-4 flex-1">
            <!-- Search -->
            <div class="flex-1 max-w-md">
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Buscar regiones..."
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>

            <!-- Filter Active Only -->
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" wire:model.live="showActiveOnly"
                    class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                <span class="text-sm text-gray-700">Solo regiones activas</span>
            </label>
        </div>

        <!-- Add Button -->
        <a href="/regions/create"
            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors inline-flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Nueva Región
        </a>
    </div>

    <!-- Regions Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Nombre
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Región Padre
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Descripción
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Productos
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Estado
                    </th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Acciones
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($regions as $region)
                    <tr class="hover:bg-gray-50 transition-colors" wire:key="region-{{ $region->id }}">
                        <td class="px-6 py-4">
                            @if ($editingId === $region->id)
                                <input type="text" wire:model="name"
                                    class="w-full px-3 py-1 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                @error('name')
                                    <span class="text-xs text-red-600">{{ $message }}</span>
                                @enderror
                            @else
                                <div class="text-sm font-medium text-gray-900">
                                    {{ $region->name }}
                                </div>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            @if ($editingId === $region->id)
                                <select wire:model="parentId"
                                    class="w-full px-3 py-1 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm">
                                    <option value="">Sin padre</option>
                                    @foreach ($allRegions as $r)
                                        @if ($r->id !== $region->id)
                                            <option value="{{ $r->id }}">{{ $r->name }}</option>
                                        @endif
                                    @endforeach
                                </select>
                            @else
                                <span class="text-sm text-gray-600">
                                    {{ $region->parent?->name ?? '-' }}
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            @if ($editingId === $region->id)
                                <textarea wire:model="description" rows="2"
                                    class="w-full px-3 py-1 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    placeholder="Descripción opcional"></textarea>
                                @error('description')
                                    <span class="text-xs text-red-600">{{ $message }}</span>
                                @enderror
                            @else
                                <span class="text-sm text-gray-600">
                                    {{ $region->description ? Str::limit($region->description, 50) : '-' }}
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-gray-600">
                                {{ $region->products_count ?? 0 }} productos
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            @if ($editingId === $region->id)
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" wire:model="active"
                                        class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                    <span class="text-sm text-gray-700">Activa</span>
                                </label>
                            @else
                                <span
                                    class="px-2 py-1 text-xs rounded-full {{ $region->active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                    {{ $region->active ? 'Activa' : 'Inactiva' }}
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right">
                            @if ($editingId === $region->id)
                                <div class="flex justify-end gap-2" wire:key="edit-buttons-{{ $region->id }}">
                                    <button wire:click="save"
                                        class="px-3 py-1 bg-green-600 text-white text-sm rounded hover:bg-green-700 transition-colors">
                                        Guardar
                                    </button>
                                    <button wire:click="cancelEdit"
                                        class="px-3 py-1 bg-gray-300 text-gray-700 text-sm rounded hover:bg-gray-400 transition-colors">
                                        Cancelar
                                    </button>
                                </div>
                            @else
                                <div class="flex justify-end gap-2" wire:key="action-buttons-{{ $region->id }}">
                                    <button wire:click="edit({{ $region->id }})"
                                        class="p-2 text-blue-600 hover:bg-blue-50 rounded transition-colors"
                                        title="Editar">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                                            </path>
                                        </svg>
                                    </button>
                                    <button wire:click="confirmDelete({{ $region->id }})"
                                        class="p-2 text-red-600 hover:bg-red-50 rounded transition-colors"
                                        title="Eliminar">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                            </path>
                                        </svg>
                                    </button>
                                </div>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                            @if ($search)
                                No se encontraron regiones que coincidan con "{{ $search }}"
                            @else
                                No hay regiones registradas
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Sección: Jerarquía de Regiones -->
    <div class="mt-8">
        <div class="mb-4">
            <h2 class="text-xl font-semibold text-gray-900">Jerarquía de Regiones</h2>
            <p class="text-sm text-gray-500 mt-1">Asigna regiones a una región padre para organizarlas en grupos.</p>
        </div>

        <!-- Asignación rápida -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h3 class="text-sm font-medium text-gray-700 mb-4">Asignar región a un padre</h3>
            <div class="flex flex-col sm:flex-row gap-4 items-end">
                <div class="flex-1">
                    <label class="block text-xs text-gray-500 mb-1">Región a asignar</label>
                    <select wire:model="assignChildId"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm">
                        <option value="">Selecciona una región...</option>
                        @foreach ($allRegions as $r)
                            <option value="{{ $r->id }}">{{ $r->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-center text-gray-400 pb-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                    </svg>
                </div>
                <div class="flex-1">
                    <label class="block text-xs text-gray-500 mb-1">Región padre</label>
                    <select wire:model="assignParentId"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm">
                        <option value="">Sin padre (raíz)</option>
                        @foreach ($allRegions as $r)
                            <option value="{{ $r->id }}">{{ $r->name }}</option>
                        @endforeach
                    </select>
                </div>
                <button wire:click="assignParent"
                    class="px-4 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 transition-colors inline-flex items-center gap-2 shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    Guardar
                </button>
            </div>
        </div>

        <!-- Vista de árbol -->
        @if ($parentRegions->isNotEmpty())
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach ($parentRegions as $parent)
                    @php $children = $allRegions->where('parent_id', $parent->id)->values(); @endphp
                    <div class="bg-white rounded-lg shadow p-4 border-l-4 border-blue-500">
                        <div class="flex items-center gap-2 mb-3">
                            <svg class="w-4 h-4 text-blue-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 7a2 2 0 012-2h4l2 2h8a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V7z" />
                            </svg>
                            <span class="font-semibold text-gray-900 text-sm">{{ $parent->name }}</span>
                            <span class="ml-auto text-xs text-gray-400">{{ $children->count() }} sub</span>
                        </div>
                        @if ($children->isEmpty())
                            <p class="text-xs text-gray-400 italic">Sin regiones hijas asignadas</p>
                        @else
                            <ul class="space-y-1">
                                @foreach ($children as $child)
                                    <li class="flex items-center gap-2 text-sm text-gray-700 pl-2">
                                        <svg class="w-3 h-3 text-gray-400 shrink-0" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 5l7 7-7 7" />
                                        </svg>
                                        {{ $child->name }}
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                @endforeach
            </div>
        @else
            <div class="bg-gray-50 rounded-lg border border-dashed border-gray-300 p-8 text-center text-sm text-gray-500">
                No hay regiones padre definidas aún. Crea regiones y asígnalas a un padre usando el formulario de arriba.
            </div>
        @endif
    </div>

    <!-- Delete Confirmation Modal -->
    @if ($deletingId)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Confirmar Eliminación</h3>
                <p class="text-gray-600 mb-6">
                    ¿Estás seguro de que deseas eliminar esta región? Esta acción no se puede deshacer.
                </p>
                <div class="flex justify-end gap-3">
                    <button wire:click="cancelDelete"
                        class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors">
                        Cancelar
                    </button>
                    <button wire:click="delete"
                        class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                        Eliminar
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
