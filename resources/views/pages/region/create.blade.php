<?php
use Livewire\Component;
use App\Services\RegionService;

new class extends Component {
    public string $name = '';
    public string $description = '';
    public bool $active = true;

    /**
     * Crea una nueva región
     */
    public function save(RegionService $regionService)
    {
        $validated = $this->validate(
            [
                'name' => 'required|string|max:255|unique:regions,name',
                'description' => 'nullable|string|max:500',
                'active' => 'boolean',
            ],
            [
                'name.required' => 'El nombre es obligatorio',
                'name.max' => 'El nombre no puede exceder 255 caracteres',
                'name.unique' => 'Ya existe una región con este nombre',
                'description.max' => 'La descripción no puede exceder 500 caracteres',
            ],
        );

        // Normalizar descripción vacía a null
        if (empty($validated['description'])) {
            $validated['description'] = null;
        }

        try {
            $regionService->create($validated);

            session()->flash('success', 'Región creada exitosamente');
            return $this->redirect('/regions');
        } catch (\Exception $e) {
            session()->flash('error', 'Error al crear la región: ' . $e->getMessage());
        }
    }

    /**
     * Cancela y regresa al listado
     */
    public function cancel()
    {
        return $this->redirect('/regions');
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
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Nueva Región</h1>
        <p class="text-gray-600">Crea una nueva región o ubicación para los productos</p>
    </div>

    <!-- Form Card -->
    <div class="bg-white rounded-lg shadow-md p-6 max-w-2xl">
        <form wire:submit="save">
            <!-- Name Field -->
            <div class="mb-6">
                <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                    Nombre <span class="text-red-600">*</span>
                </label>
                <input type="text" id="name" wire:model="name"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('name') border-red-500 @enderror"
                    placeholder="Ej: Norte, Sur, Centro, Zona 1">
                @error('name')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Description Field -->
            <div class="mb-6">
                <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                    Descripción
                </label>
                <textarea id="description" wire:model="description" rows="4"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('description') border-red-500 @enderror"
                    placeholder="Descripción de la región (opcional)"></textarea>
                @error('description')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-sm text-gray-500">
                    Agrega detalles adicionales sobre esta región o ubicación
                </p>
            </div>

            <!-- Active Field -->
            <div class="mb-6">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" wire:model="active"
                        class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                    <span class="text-sm font-medium text-gray-700">Región activa</span>
                </label>
                <p class="mt-1 text-sm text-gray-500 ml-6">
                    Las regiones inactivas no estarán disponibles para asignar a productos
                </p>
            </div>

            <!-- Form Actions -->
            <div class="flex justify-end gap-3 pt-4 border-t border-gray-200">
                <button type="button" wire:click="cancel"
                    class="px-6 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors">
                    Cancelar
                </button>
                <button type="submit"
                    class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors inline-flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    Crear Región
                </button>
            </div>
        </form>
    </div>

    <!-- Help Text -->
    <div class="mt-6 max-w-2xl bg-blue-50 border border-blue-200 rounded-lg p-4">
        <div class="flex items-start gap-3">
            <svg class="w-6 h-6 text-blue-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <div class="text-sm text-blue-800">
                <p class="font-medium mb-1">Información sobre regiones:</p>
                <ul class="list-disc list-inside space-y-1">
                    <li>Las regiones permiten organizar productos por ubicación geográfica o zona</li>
                    <li>Cada producto puede pertenecer a una región específica</li>
                    <li>Las regiones inactivas no aparecerán al crear o editar productos</li>
                    <li>No puedes eliminar regiones que tengan productos asociados</li>
                </ul>
            </div>
        </div>
    </div>
</div>
