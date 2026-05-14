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

final class ItemController extends Controller
{
    public function index(Request $request): ResourceCollection
    {
        $this->authorize('viewAny', Item::class);

        $query = Item::with('category');

        if ($request->boolean('active_only', true)) {
            $query->where('is_active', true);
        }

        if ($catId = $request->integer('filter.category_id') ?: null) {
            $query->where('category_id', $catId);
        }

        if ($search = $request->string('search')->trim()) {
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"])
                  ->orWhereRaw('LOWER(sku) LIKE ?', ["%{$search}%"])
                  ->orWhereRaw('LOWER(barcode) LIKE ?', ["%{$search}%"]);
            });
        }

        return ItemResource::collection(
            $query->orderBy('name')->paginate($request->integer('per_page', 20))
        );
    }

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

    public function store(StoreItemRequest $request)
    {
        $item = Item::create([
            ...$request->validated(),
            'created_by' => $request->user()->id,
        ]);

        return (new ItemResource($item->load('category')))->response()->setStatusCode(201);
    }

    public function show(Item $item): ItemResource
    {
        $this->authorize('view', $item);
        return new ItemResource($item->load('category', 'stocks'));
    }

    public function update(UpdateItemRequest $request, Item $item): ItemResource
    {
        $item->update([
            ...$request->validated(),
            'updated_by' => $request->user()->id,
        ]);

        return new ItemResource($item->fresh()->load('category'));
    }

    public function destroy(Request $request, Item $item): JsonResponse
    {
        $this->authorize('delete', $item);
        $item->delete();
        return response()->json(null, 204);
    }
}
