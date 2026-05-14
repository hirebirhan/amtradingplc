<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Categories\StoreCategoryRequest;
use App\Http\Requests\Api\V1\Categories\UpdateCategoryRequest;
use App\Http\Resources\Api\V1\CategoryResource;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

final class CategoryController extends Controller
{
    public function index(Request $request): ResourceCollection
    {
        $this->authorize('viewAny', Category::class);

        $query = Category::query();

        if ($request->boolean('active_only')) {
            $query->where('is_active', true);
        }

        if ($search = $request->string('search')->trim()) {
            $query->where('name', 'like', "%{$search}%");
        }

        if ($request->has('filter.parent_id')) {
            $query->where('parent_id', $request->input('filter.parent_id'));
        }

        return CategoryResource::collection(
            $query->with('children')->orderBy('name')->paginate($request->integer('per_page', 50))
        );
    }

    public function store(StoreCategoryRequest $request): CategoryResource
    {
        $category = Category::create([
            ...$request->validated(),
            'created_by' => $request->user()->id,
        ]);

        return new CategoryResource($category);
    }

    public function show(Category $category): CategoryResource
    {
        $this->authorize('view', $category);
        return new CategoryResource($category->load('parent', 'children'));
    }

    public function update(UpdateCategoryRequest $request, Category $category): CategoryResource
    {
        $category->update([
            ...$request->validated(),
            'updated_by' => $request->user()->id,
        ]);

        return new CategoryResource($category->fresh()->load('parent', 'children'));
    }

    public function destroy(Request $request, Category $category): JsonResponse
    {
        $this->authorize('delete', $category);
        $category->delete();
        return response()->json(null, 204);
    }
}
