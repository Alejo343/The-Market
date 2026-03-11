<?php
use Livewire\Component;
use App\Services\TaxService;

new class extends Component {
    public string $name = '';
    public string $percentage = '';
    public bool $active = true;

    /**
     * Crea un nuevo impuesto
     */
    public function save(TaxService $taxService)
    {
        $validated = $this->validate(
            [
                'name' => 'required|string|max:50|unique:taxes,name',
                'percentage' => 'required|numeric|min:0|max:100',
                'active' => 'boolean',
            ],
            [
                'name.required' => 'El nombre es obligatorio',
                'name.max' => 'El nombre no puede exceder 50 caracteres',
                'name.unique' => 'Ya existe un impuesto con este nombre',
                'percentage.required' => 'El porcentaje es obligatorio',
                'percentage.numeric' => 'El porcentaje debe ser un número',
                'percentage.min' => 'El porcentaje debe ser mayor o igual a 0',
                'percentage.max' => 'El porcentaje no puede ser mayor a 100',
            ],
        );

        try {
            $taxService->create($validated);

            session()->flash('success', 'Impuesto creado exitosamente');
            return $this->redirect('/taxes');
        } catch (\Exception $e) {
            session()->flash('error', 'Error al crear el impuesto: ' . $e->getMessage());
        }
    }

    /**
     * Cancela y regresa al listado
     */
    public function cancel()
    {
        return $this->redirect('/taxes');
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
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Nuevo Impuesto</h1>
        <p class="text-gray-600">Crea un nuevo impuesto aplicable a los productos</p>
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
                    placeholder="Ej: IVA, ISR, IEPS">
                @error('name')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Percentage Field -->
            <div class="mb-6">
                <label for="percentage" class="block text-sm font-medium text-gray-700 mb-2">
                    Porcentaje <span class="text-red-600">*</span>
                </label>
                <div class="relative">
                    <input type="number" step="0.01" id="percentage" wire:model="percentage"
                        class="w-full px-4 py-2 pr-12 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('percentage') border-red-500 @enderror"
                        placeholder="0.00">
                    <span class="absolute right-4 top-2 text-gray-500">%</span>
                </div>
                @error('percentage')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-sm text-gray-500">
                    Ingresa el porcentaje del impuesto (ej: 16 para IVA del 16%)
                </p>
            </div>

            <!-- Active Field -->
            <div class="mb-6">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" wire:model="active"
                        class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                    <span class="text-sm font-medium text-gray-700">Impuesto activo</span>
                </label>
                <p class="mt-1 text-sm text-gray-500 ml-6">
                    Los impuestos inactivos no estarán disponibles para asignar a productos
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
                    Crear Impuesto
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
                <p class="font-medium mb-1">Información sobre impuestos:</p>
                <ul class="list-disc list-inside space-y-1">
                    <li>Los impuestos se aplican a las variantes de productos de forma opcional</li>
                    <li>El porcentaje se calculará automáticamente sobre el precio del producto</li>
                    <li>Puedes crear diferentes tipos de impuestos (IVA, ISR, IEPS, etc.)</li>
                    <li>Los impuestos inactivos no se mostrarán al crear o editar productos</li>
                </ul>
            </div>
        </div>
    </div>
</div>
