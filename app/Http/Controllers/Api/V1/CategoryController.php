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
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Categories')]
final class CategoryController extends Controller
{
    #[OA\Get(path: '/categories', summary: 'List categories', security: [['sanctum' => []]], tags: ['Categories'],
        parameters: [
            new OA\Parameter(name: 'active_only', in: 'query', schema: new OA\Schema(type: 'boolean')),
            new OA\Parameter(name: 'search', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'filter[parent_id]', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', default: 50)),
        ],
        responses: [new OA\Response(response: 200, description: 'Category list')]
    )]
    public function index(Request $request): ResourceCollection
    {
        $this->authorize('viewAny', Category::class);
        $query = Category::query();
        if ($request->boolean('active_only')) { $query->where('is_active', true); }
        if ($search = $request->string('search')->trim()) { $query->where('name', 'like', "%{$search}%"); }
        if ($request->has('filter.parent_id')) { $query->where('parent_id', $request->input('filter.parent_id')); }
        return CategoryResource::collection($query->with('children')->orderBy('name')->paginate($request->integer('per_page', 50)));
    }

    #[OA\Post(path: '/categories', summary: 'Create a category', security: [['sanctum' => []]], tags: ['Categories'],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['name'],
            properties: [
                new OA\Property(property: 'name', type: 'string'),
                new OA\Property(property: 'parent_id', type: 'integer', nullable: true),
            ]
        )),
        responses: [new OA\Response(response: 201, description: 'Created')]
    )]
    public function store(StoreCategoryRequest $request): CategoryResource
    {
        $category = Category::create([...$request->validated(), 'created_by' => $request->user()->id]);
        return new CategoryResource($category);
    }

    #[OA\Get(path: '/categories/{id}', summary: 'Get a category', security: [['sanctum' => []]], tags: ['Categories'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Category detail')]
    )]
    public function show(Category $category): CategoryResource
    {
        $this->authorize('view', $category);
        return new CategoryResource($category->load('parent', 'children'));
    }

    #[OA\Put(path: '/categories/{id}', summary: 'Update a category', security: [['sanctum' => []]], tags: ['Categories'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(content: new OA\JsonContent(
            properties: [new OA\Property(property: 'name', type: 'string'), new OA\Property(property: 'parent_id', type: 'integer')]
        )),
        responses: [new OA\Response(response: 200, description: 'Updated')]
    )]
    public function update(UpdateCategoryRequest $request, Category $category): CategoryResource
    {
        $category->update([...$request->validated(), 'updated_by' => $request->user()->id]);
        return new CategoryResource($category->fresh()->load('parent', 'children'));
    }

    #[OA\Delete(path: '/categories/{id}', summary: 'Delete a category', security: [['sanctum' => []]], tags: ['Categories'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 204, description: 'Deleted')]
    )]
    public function destroy(Request $request, Category $category): JsonResponse
    {
        $this->authorize('delete', $category);
        $category->delete();
        return response()->json(null, 204);
    }
}
