<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Suppliers\StoreSupplierRequest;
use App\Http\Requests\Api\V1\Suppliers\UpdateSupplierRequest;
use App\Http\Resources\Api\V1\SupplierResource;
use App\Models\Supplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

final class SupplierController extends Controller
{
    public function index(Request $request): ResourceCollection
    {
        $this->authorize('viewAny', Supplier::class);

        $query = Supplier::query();

        if ($branchId = $request->integer('filter.branch_id') ?: null) {
            $query->where('branch_id', $branchId);
        }

        if ($search = $request->string('search')->trim()) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        return SupplierResource::collection(
            $query->orderBy('name')->paginate($request->integer('per_page', 20))
        );
    }

    public function store(StoreSupplierRequest $request)
    {
        $supplier = Supplier::create([
            ...$request->validated(),
            'created_by' => $request->user()->id,
        ]);

        return (new SupplierResource($supplier))->response()->setStatusCode(201);
    }

    public function show(Supplier $supplier): SupplierResource
    {
        $this->authorize('view', $supplier);
        return new SupplierResource($supplier->load('branch'));
    }

    public function update(UpdateSupplierRequest $request, Supplier $supplier): SupplierResource
    {
        $supplier->update([
            ...$request->validated(),
            'updated_by' => $request->user()->id,
        ]);

        return new SupplierResource($supplier->fresh()->load('branch'));
    }

    public function destroy(Request $request, Supplier $supplier): JsonResponse
    {
        $this->authorize('delete', $supplier);
        $supplier->update(['deleted_by' => $request->user()->id]);
        $supplier->delete();
        return response()->json(null, 204);
    }
}
