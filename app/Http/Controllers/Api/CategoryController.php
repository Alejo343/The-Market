<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Category::query();

        // Filtrar solo categorías principales si se solicita
        if ($request->boolean('root_only')) {
            $query->rootCategories();
        }

        // Incluir relaciones si se solicitan
        if ($request->has('include')) {
            $includes = explode(',', $request->input('include'));
            $query->with($includes);
        }

        // Búsqueda por nombre
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->input('search') . '%');
        }

        $categories = $query->orderBy('name')->get();

        return CategoryResource::collection($categories);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $category = Category::create($request->validated());

        return (new CategoryResource($category))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Category $category): CategoryResource
    {
        // Cargar relaciones si se solicitan
        if ($request->has('include')) {
            $includes = explode(',', $request->input('include'));
            $category->load($includes);
        }

        return new CategoryResource($category);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCategoryRequest $request, Category $category): CategoryResource
    {
        $category->update($request->validated());

        return new CategoryResource($category);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Category $category): JsonResponse
    {
        // Verificar si tiene subcategorías
        if ($category->hasSubcategories()) {
            return response()->json([
                'message' => 'No se puede eliminar una categoría que tiene subcategorías',
                'error' => 'category_has_subcategories'
            ], 422);
        }

        // Verificar si tiene productos
        if ($category->products()->exists()) {
            return response()->json([
                'message' => 'No se puede eliminar una categoría que tiene productos asociados',
                'error' => 'category_has_products'
            ], 422);
        }

        $category->delete();

        return response()->json([
            'message' => 'Categoría eliminada exitosamente'
        ], 200);
    }
}
