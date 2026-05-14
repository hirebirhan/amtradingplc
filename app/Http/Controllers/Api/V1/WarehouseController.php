<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Warehouses\StoreWarehouseRequest;
use App\Http\Requests\Api\V1\Warehouses\UpdateWarehouseRequest;
use App\Http\Resources\Api\V1\WarehouseResource;
use App\Models\Warehouse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Warehouses')]
final class WarehouseController extends Controller
{
    #[OA\Get(path: '/warehouses', summary: 'List warehouses', security: [['sanctum' => []]], tags: ['Warehouses'],
        parameters: [
            new OA\Parameter(name: 'filter[branch_id]', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', default: 20)),
        ],
        responses: [new OA\Response(response: 200, description: 'Paginated warehouse list')]
    )]
    public function index(Request $request): ResourceCollection
    {
        $this->authorize('viewAny', Warehouse::class);
        $user = $request->user();
        $query = Warehouse::with('branches');
        if (! $user->isSuperAdmin() && ! $user->isGeneralManager()) {
            $ids = \App\Helpers\UserHelper::getAccessibleWarehouseIds();
            $query->whereIn('id', $ids);
        }
        if ($branchId = $request->integer('filter.branch_id') ?: null) {
            $query->whereHas('branches', fn ($q) => $q->where('branches.id', $branchId));
        }
        return WarehouseResource::collection($query->orderBy('name')->paginate($request->integer('per_page', 20)));
    }

    #[OA\Post(path: '/warehouses', summary: 'Create a warehouse', security: [['sanctum' => []]], tags: ['Warehouses'],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['name', 'type'],
            properties: [
                new OA\Property(property: 'name', type: 'string'),
                new OA\Property(property: 'type', type: 'string', enum: ['main', 'secondary', 'transit']),
                new OA\Property(property: 'location', type: 'string'),
                new OA\Property(property: 'is_active', type: 'boolean'),
                new OA\Property(property: 'branch_ids', type: 'array', items: new OA\Items(type: 'integer')),
            ]
        )),
        responses: [new OA\Response(response: 201, description: 'Warehouse created')]
    )]
    public function store(StoreWarehouseRequest $request): WarehouseResource
    {
        $data = $request->validated();
        $branchIds = $data['branch_ids'] ?? [];
        unset($data['branch_ids']);
        $warehouse = Warehouse::create([...$data, 'created_by' => $request->user()->id]);
        if ($branchIds) { $warehouse->branches()->sync($branchIds); }
        return new WarehouseResource($warehouse->load('branches'));
    }

    #[OA\Get(path: '/warehouses/{id}', summary: 'Get a warehouse', security: [['sanctum' => []]], tags: ['Warehouses'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Warehouse detail')]
    )]
    public function show(Warehouse $warehouse): WarehouseResource
    {
        $this->authorize('view', $warehouse);
        return new WarehouseResource($warehouse->load('branches'));
    }

    #[OA\Put(path: '/warehouses/{id}', summary: 'Update a warehouse', security: [['sanctum' => []]], tags: ['Warehouses'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'name', type: 'string'),
                new OA\Property(property: 'type', type: 'string'),
                new OA\Property(property: 'branch_ids', type: 'array', items: new OA\Items(type: 'integer')),
            ]
        )),
        responses: [new OA\Response(response: 200, description: 'Updated')]
    )]
    public function update(UpdateWarehouseRequest $request, Warehouse $warehouse): WarehouseResource
    {
        $data = $request->validated();
        $branchIds = $data['branch_ids'] ?? null;
        unset($data['branch_ids']);
        $warehouse->update([...$data, 'updated_by' => $request->user()->id]);
        if ($branchIds !== null) { $warehouse->branches()->sync($branchIds); }
        return new WarehouseResource($warehouse->fresh()->load('branches'));
    }

    #[OA\Delete(path: '/warehouses/{id}', summary: 'Delete a warehouse', security: [['sanctum' => []]], tags: ['Warehouses'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 204, description: 'Deleted')]
    )]
    public function destroy(Request $request, Warehouse $warehouse): JsonResponse
    {
        $this->authorize('delete', $warehouse);
        $warehouse->update(['deleted_by' => $request->user()->id]);
        $warehouse->delete();
        return response()->json(null, 204);
    }
}
