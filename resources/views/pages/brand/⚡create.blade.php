<?php
use Livewire\Component;
use App\Services\BrandService;

new class extends Component {
    public string $name = '';

    /**
     * Crea una nueva marca
     */
    public function save(BrandService $brandService)
    {
        $validated = $this->validate(
            [
                'name' => 'required|string|max:100|unique:brands,name',
            ],
            [
                'name.required' => 'El nombre es obligatorio',
                'name.max' => 'El nombre no puede exceder 100 caracteres',
                'name.unique' => 'Ya existe una marca con este nombre',
            ],
        );

        try {
            $brandService->create($validated);

            session()->flash('success', 'Marca creada exitosamente');
            return $this->redirect('/brands');
        } catch (\Exception $e) {
            session()->flash('error', 'Error al crear la marca: ' . $e->getMessage());
        }
    }

    /**
     * Cancela y regresa al listado
     */
    public function cancel()
    {
        return $this->redirect('/brands');
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
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Nueva Marca</h1>
        <p class="text-gray-600">Crea una nueva marca de productos</p>
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
                    placeholder="Ingresa el nombre de la marca">
                @error('name')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
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
                    Crear Marca
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
                <p class="font-medium mb-1">Información útil:</p>
                <ul class="list-disc list-inside space-y-1">
                    <li>El nombre de la marca debe ser único</li>
                    <li>Las marcas facilitan la organización y búsqueda de productos</li>
                    <li>Podrás editar o eliminar la marca más adelante (si no tiene productos asociados)</li>
                </ul>
            </div>
        </div>
    </div>
</div>
