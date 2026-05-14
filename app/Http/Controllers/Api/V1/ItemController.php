<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Items\StoreItemRequest;
use App\Http\Requests\Api\V1\Items\UpdateItemRequest;
use App\Http\Resources\Api\V1\ItemResource;
use App\Models\Item;
use App\Services\ItemSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Items')]
final class ItemController extends Controller
{
    #[OA\Get(path: '/items', summary: 'List items', security: [['sanctum' => []]], tags: ['Items'],
        parameters: [
            new OA\Parameter(name: 'filter[category_id]', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'search', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'active_only', in: 'query', schema: new OA\Schema(type: 'boolean', default: true)),
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', default: 20)),
        ],
        responses: [new OA\Response(response: 200, description: 'Item list')]
    )]
    public function index(Request $request): ResourceCollection
    {
        $this->authorize('viewAny', Item::class);
        $query = Item::with('category');
        if ($request->boolean('active_only', true)) { $query->where('is_active', true); }
        if ($catId = $request->integer('filter.category_id') ?: null) { $query->where('category_id', $catId); }
        if ($search = $request->string('search')->trim()) {
            $query->where(fn ($q) => $q->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"])->orWhereRaw('LOWER(sku) LIKE ?', ["%{$search}%"])->orWhereRaw('LOWER(barcode) LIKE ?', ["%{$search}%"]));
        }
        return ItemResource::collection($query->orderBy('name')->paginate($request->integer('per_page', 20)));
    }

    #[OA\Get(path: '/items/search', summary: 'Search items (for dropdowns)', security: [['sanctum' => []]], tags: ['Items'],
        parameters: [
            new OA\Parameter(name: 'q', in: 'query', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'context', in: 'query', schema: new OA\Schema(type: 'string', enum: ['purchase', 'sale', 'transfer'], default: 'purchase')),
            new OA\Parameter(name: 'warehouse_id', in: 'query', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [new OA\Response(response: 200, description: 'Matched items')]
    )]
    public function search(Request $request, ItemSearchService $service): ResourceCollection
    {
        $this->authorize('viewAny', Item::class);
        $items = $service->search(
            query: (string) $request->string('q'),
            context: (string) $request->string('context', 'purchase'),
            warehouseId: $request->integer('warehouse_id') ?: null,
            user: $request->user(),
        );
        return ItemResource::collection($items);
    }

    #[OA\Post(path: '/items', summary: 'Create an item', security: [['sanctum' => []]], tags: ['Items'],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['name', 'sku', 'category_id', 'unit', 'cost_price', 'selling_price'],
            properties: [
                new OA\Property(property: 'name', type: 'string'),
                new OA\Property(property: 'sku', type: 'string'),
                new OA\Property(property: 'barcode', type: 'string'),
                new OA\Property(property: 'category_id', type: 'integer'),
                new OA\Property(property: 'unit', type: 'string', example: 'piece'),
                new OA\Property(property: 'unit_quantity', type: 'integer', example: 1),
                new OA\Property(property: 'cost_price', type: 'number'),
                new OA\Property(property: 'selling_price', type: 'number'),
                new OA\Property(property: 'min_stock_level', type: 'integer'),
            ]
        )),
        responses: [new OA\Response(response: 201, description: 'Item created')]
    )]
    public function store(StoreItemRequest $request)
    {
        $item = Item::create([...$request->validated(), 'created_by' => $request->user()->id]);
        return (new ItemResource($item->load('category')))->response()->setStatusCode(201);
    }

    #[OA\Get(path: '/items/{id}', summary: 'Get an item', security: [['sanctum' => []]], tags: ['Items'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Item detail')]
    )]
    public function show(Item $item): ItemResource
    {
        $this->authorize('view', $item);
        return new ItemResource($item->load('category', 'stocks'));
    }

    #[OA\Put(path: '/items/{id}', summary: 'Update an item', security: [['sanctum' => []]], tags: ['Items'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(content: new OA\JsonContent(
            properties: [new OA\Property(property: 'name', type: 'string'), new OA\Property(property: 'selling_price', type: 'number')]
        )),
        responses: [new OA\Response(response: 200, description: 'Updated')]
    )]
    public function update(UpdateItemRequest $request, Item $item): ItemResource
    {
        $item->update([...$request->validated(), 'updated_by' => $request->user()->id]);
        return new ItemResource($item->fresh()->load('category'));
    }

    #[OA\Delete(path: '/items/{id}', summary: 'Delete an item', security: [['sanctum' => []]], tags: ['Items'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 204, description: 'Deleted')]
    )]
    public function destroy(Request $request, Item $item): JsonResponse
    {
        $this->authorize('delete', $item);
        $item->delete();
        return response()->json(null, 204);
    }
}
