<?php

namespace App\Services;

use App\Models\Category;
use Illuminate\Database\Eloquent\Collection;
use Exception;

class CategoryService
{
    /**
     * Lista categorías con filtros opcionales
     */
    public function list(
        bool $rootOnly = false,
        ?array $include = null,
        ?string $search = null
    ): Collection {
        $query = Category::query();

        if ($rootOnly) {
            $query->rootCategories();
        }

        if ($include) {
            $query->with($include);
        }

        if ($search) {
            $query->where('name', 'like', "%{$search}%");
        }

        return $query->orderBy('name')->get();
    }

    /**
     * Obtiene solo categorías raíz con sus hijos
     */
    public function getRootCategories(): Collection
    {
        return $this->list(
            rootOnly: true,
            include: ['subcategories'] // ✅ CAMBIA ESTO
        );
    }

    /**
     * Obtiene todas las categorías con su padre
     */
    public function getAll(): Collection
    {
        return $this->list(
            include: ['parent']
        );
    }

    /**
     * Busca categorías por nombre
     */
    public function search(string $query): Collection
    {
        return $this->list(search: $query);
    }

    public function create(array $data): Category
    {
        return Category::create($data);
    }

    public function show(Category $category, ?array $include = null): Category
    {
        if ($include) {
            $category->load($include);
        }

        return $category;
    }

    public function update(Category $category, array $data): Category
    {
        $category->update($data);

        return $category;
    }

    public function delete(Category $category): void
    {
        if ($category->hasSubcategories()) {
            throw new Exception('CATEGORY_HAS_SUBCATEGORIES');
        }

        if ($category->products()->exists()) {
            throw new Exception('CATEGORY_HAS_PRODUCTS');
        }

        $category->delete();
    }
}
