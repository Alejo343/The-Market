<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Services\CategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Exception;

class CategoryController extends Controller
{
    public function __construct(
        protected CategoryService $service
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        // Convierte Request a parámetros del service
        $categories = $this->service->list(
            rootOnly: $request->boolean('root_only'),
            include: $request->has('include')
                ? explode(',', $request->input('include'))
                : null,
            search: $request->filled('search')
                ? $request->input('search')
                : null
        );

        return CategoryResource::collection($categories);
    }

    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $category = $this->service->create(
            $request->validated()
        );

        return (new CategoryResource($category))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Request $request, Category $category): CategoryResource
    {
        // Convierte string CSV a array
        $include = $request->has('include')
            ? explode(',', $request->input('include'))
            : null;

        return new CategoryResource(
            $this->service->show($category, $include)
        );
    }

    public function update(UpdateCategoryRequest $request, Category $category): CategoryResource
    {
        return new CategoryResource(
            $this->service->update(
                $category,
                $request->validated()
            )
        );
    }

    public function destroy(Category $category): JsonResponse
    {
        try {
            $this->service->delete($category);

            return response()->json([
                'message' => 'Categoría eliminada exitosamente'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => match ($e->getMessage()) {
                    'CATEGORY_HAS_SUBCATEGORIES' =>
                    'No se puede eliminar una categoría que tiene subcategorías',
                    'CATEGORY_HAS_PRODUCTS' =>
                    'No se puede eliminar una categoría que tiene productos asociados',
                    default => 'Error inesperado'
                }
            ], 422);
        }
    }
}
