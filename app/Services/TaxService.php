<?php

namespace App\Services;

use App\Models\Tax;
use Illuminate\Database\Eloquent\Collection;
use Exception;

class TaxService
{
    /**
     * Lista impuestos con filtros opcionales
     */
    public function list(
        bool $activeOnly = false,
        ?string $search = null
    ): Collection {
        $query = Tax::query();

        if ($activeOnly) {
            $query->active();
        }

        if ($search) {
            $query->where('name', 'like', "%{$search}%");
        }

        return $query->orderBy('percentage')->get();
    }

    /**
     * Obtiene todos los impuestos
     */
    public function getAll(): Collection
    {
        return $this->list();
    }

    /**
     * Obtiene impuestos activos
     */
    public function getActive(): Collection
    {
        return $this->list(activeOnly: true);
    }

    /**
     * Busca impuestos por nombre
     */
    public function search(string $query): Collection
    {
        return $this->list(search: $query);
    }

    /**
     * Crea un nuevo impuesto
     */
    public function create(array $data): Tax
    {
        return Tax::create($data);
    }

    /**
     * Obtiene un impuesto específico
     */
    public function show(Tax $tax): Tax
    {
        return $tax;
    }

    /**
     * Actualiza un impuesto
     */
    public function update(Tax $tax, array $data): Tax
    {
        $tax->update($data);

        return $tax;
    }

    /**
     * Elimina un impuesto
     */
    public function delete(Tax $tax): void
    {
        if ($tax->productVariants()->exists()) {
            throw new Exception('TAX_IN_USE');
        }

        $tax->delete();
    }
}
