<?php
use Livewire\Component;
use App\Services\WeightLotService;
use App\Services\ProductService;

new class extends Component {
    public int $product_id = 0;
    public string $initial_weight = '';
    public string $price_per_kg = '';
    public string $expires_at = '';
    public bool $active = true;

    /**
     * Obtiene productos para el selector
     */
    public function with(ProductService $productService): array
    {
        return [
            'products' => $productService->getBySaleType('weight'),
        ];
    }

    /**
     * Crea un nuevo lote
     */
    public function save(WeightLotService $lotService)
    {
        $validated = $this->validate(
            [
                'product_id' => 'required|exists:products,id',
                'initial_weight' => 'required|numeric|min:0.001',
                'price_per_kg' => 'required|numeric|min:0',
                'expires_at' => 'nullable|date|after:today',
                'active' => 'boolean',
            ],
            [
                'product_id.required' => 'El producto es obligatorio',
                'product_id.exists' => 'El producto seleccionado no existe',
                'initial_weight.required' => 'El peso inicial es obligatorio',
                'initial_weight.numeric' => 'El peso inicial debe ser un número',
                'initial_weight.min' => 'El peso inicial debe ser mayor a 0',
                'price_per_kg.required' => 'El precio por kg es obligatorio',
                'price_per_kg.numeric' => 'El precio por kg debe ser un número',
                'price_per_kg.min' => 'El precio por kg debe ser mayor o igual a 0',
                'expires_at.date' => 'La fecha de vencimiento debe ser una fecha válida',
                'expires_at.after' => 'La fecha de vencimiento debe ser posterior a hoy',
            ],
        );

        // El peso disponible es igual al peso inicial al crear
        $validated['available_weight'] = $validated['initial_weight'];

        try {
            $lotService->create($validated);

            session()->flash('success', 'Lote creado exitosamente');
            return $this->redirect('/weight-lots');
        } catch (\Exception $e) {
            session()->flash('error', 'Error al crear el lote: ' . $e->getMessage());
        }
    }

    /**
     * Cancela y regresa al listado
     */
    public function cancel()
    {
        return $this->redirect('/weight-lots');
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
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Nuevo Lote de Peso</h1>
        <p class="text-gray-600">Crea un nuevo lote para un producto vendido por peso</p>
    </div>

    <!-- Form Card -->
    <div class="bg-white rounded-lg shadow-md p-6 max-w-3xl">
        <form wire:submit="save">
            <!-- Product Field -->
            <div class="mb-6">
                <label for="product_id" class="block text-sm font-medium text-gray-700 mb-2">
                    Producto <span class="text-red-600">*</span>
                </label>
                <select id="product_id" wire:model="product_id"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('product_id') border-red-500 @enderror">
                    <option value="0">Seleccionar producto</option>
                    @foreach ($products as $product)
                        <option value="{{ $product->id }}">{{ $product->name }}</option>
                    @endforeach
                </select>
                @error('product_id')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-sm text-gray-500">
                    Solo se muestran productos vendidos por peso
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <!-- Initial Weight Field -->
                <div>
                    <label for="initial_weight" class="block text-sm font-medium text-gray-700 mb-2">
                        Peso Inicial (kg) <span class="text-red-600">*</span>
                    </label>
                    <input type="number" step="0.001" id="initial_weight" wire:model="initial_weight"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('initial_weight') border-red-500 @enderror"
                        placeholder="0.000">
                    @error('initial_weight')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-sm text-gray-500">
                        Peso total del lote al recibirlo
                    </p>
                </div>

                <!-- Price per kg Field -->
                <div>
                    <label for="price_per_kg" class="block text-sm font-medium text-gray-700 mb-2">
                        Precio por Kilogramo <span class="text-red-600">*</span>
                    </label>
                    <div class="relative">
                        <span class="absolute left-3 top-2 text-gray-500">$</span>
                        <input type="number" step="0.01" id="price_per_kg" wire:model="price_per_kg"
                            class="w-full pl-8 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('price_per_kg') border-red-500 @enderror"
                            placeholder="0.00">
                    </div>
                    @error('price_per_kg')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Expires At Field -->
            <div class="mb-6">
                <label for="expires_at" class="block text-sm font-medium text-gray-700 mb-2">
                    Fecha de Vencimiento
                </label>
                <input type="date" id="expires_at" wire:model="expires_at"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('expires_at') border-red-500 @enderror">
                @error('expires_at')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-sm text-gray-500">
                    Opcional - El sistema alertará cuando el lote esté próximo a vencer
                </p>
            </div>

            <!-- Active Field -->
            <div class="mb-6">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" wire:model="active"
                        class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                    <span class="text-sm font-medium text-gray-700">Lote activo</span>
                </label>
                <p class="mt-1 text-sm text-gray-500 ml-6">
                    Los lotes inactivos no estarán disponibles para venta
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
                    Crear Lote
                </button>
            </div>
        </form>
    </div>

    <!-- Help Text -->
    <div class="mt-6 max-w-3xl bg-blue-50 border border-blue-200 rounded-lg p-4">
        <div class="flex items-start gap-3">
            <svg class="w-6 h-6 text-blue-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <div class="text-sm text-blue-800">
                <p class="font-medium mb-1">Información sobre lotes de peso:</p>
                <ul class="list-disc list-inside space-y-1">
                    <li>Los lotes representan carnes, frutas, verduras o cualquier producto que se vende por kilogramo
                    </li>
                    <li>El peso disponible se irá reduciendo automáticamente con cada venta</li>
                    <li>El sistema alertará sobre lotes próximos a vencer (7 días antes)</li>
                    <li>Los lotes vencidos o agotados se desactivarán automáticamente</li>
                    <li>Puedes tener múltiples lotes activos del mismo producto con diferentes precios</li>
                </ul>
            </div>
        </div>
    </div>
</div>
