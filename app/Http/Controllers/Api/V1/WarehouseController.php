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

final class WarehouseController extends Controller
{
    public function index(Request $request): ResourceCollection
    {
        $this->authorize('viewAny', Warehouse::class);

        $user = $request->user();
        $query = Warehouse::with('branches');

        // Non-admins only see their accessible warehouses
        if (! $user->isSuperAdmin() && ! $user->isGeneralManager()) {
            $ids = \App\Helpers\UserHelper::getAccessibleWarehouseIds();
            $query->whereIn('id', $ids);
        }

        if ($branchId = $request->integer('filter.branch_id') ?: null) {
            $query->whereHas('branches', fn ($q) => $q->where('branches.id', $branchId));
        }

        return WarehouseResource::collection(
            $query->orderBy('name')->paginate($request->integer('per_page', 20))
        );
    }

    public function store(StoreWarehouseRequest $request): WarehouseResource
    {
        $data = $request->validated();
        $branchIds = $data['branch_ids'] ?? [];
        unset($data['branch_ids']);

        $warehouse = Warehouse::create([...$data, 'created_by' => $request->user()->id]);

        if ($branchIds) {
            $warehouse->branches()->sync($branchIds);
        }

        return new WarehouseResource($warehouse->load('branches'));
    }

    public function show(Warehouse $warehouse): WarehouseResource
    {
        $this->authorize('view', $warehouse);
        return new WarehouseResource($warehouse->load('branches'));
    }

    public function update(UpdateWarehouseRequest $request, Warehouse $warehouse): WarehouseResource
    {
        $data = $request->validated();
        $branchIds = $data['branch_ids'] ?? null;
        unset($data['branch_ids']);

        $warehouse->update([...$data, 'updated_by' => $request->user()->id]);

        if ($branchIds !== null) {
            $warehouse->branches()->sync($branchIds);
        }

        return new WarehouseResource($warehouse->fresh()->load('branches'));
    }

    public function destroy(Request $request, Warehouse $warehouse): JsonResponse
    {
        $this->authorize('delete', $warehouse);
        $warehouse->update(['deleted_by' => $request->user()->id]);
        $warehouse->delete();
        return response()->json(null, 204);
    }
}
