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
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Suppliers')]
final class SupplierController extends Controller
{
    #[OA\Get(path: '/suppliers', summary: 'List suppliers', security: [['sanctum' => []]], tags: ['Suppliers'],
        parameters: [
            new OA\Parameter(name: 'search', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', default: 20)),
        ],
        responses: [new OA\Response(response: 200, description: 'Supplier list')]
    )]
    public function index(Request $request): ResourceCollection
    {
        $this->authorize('viewAny', Supplier::class);
        $query = Supplier::query();
        if ($branchId = $request->integer('filter.branch_id') ?: null) { $query->where('branch_id', $branchId); }
        if ($search = $request->string('search')->trim()) {
            $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"));
        }
        return SupplierResource::collection($query->orderBy('name')->paginate($request->integer('per_page', 20)));
    }

    #[OA\Post(path: '/suppliers', summary: 'Create a supplier', security: [['sanctum' => []]], tags: ['Suppliers'],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['name'],
            properties: [
                new OA\Property(property: 'name', type: 'string'),
                new OA\Property(property: 'email', type: 'string', format: 'email'),
                new OA\Property(property: 'phone', type: 'string'),
                new OA\Property(property: 'address', type: 'string'),
            ]
        )),
        responses: [new OA\Response(response: 201, description: 'Created')]
    )]
    public function store(StoreSupplierRequest $request)
    {
        $supplier = Supplier::create([...$request->validated(), 'created_by' => $request->user()->id]);
        return (new SupplierResource($supplier))->response()->setStatusCode(201);
    }

    #[OA\Get(path: '/suppliers/{id}', summary: 'Get a supplier', security: [['sanctum' => []]], tags: ['Suppliers'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Supplier detail')]
    )]
    public function show(Supplier $supplier): SupplierResource
    {
        $this->authorize('view', $supplier);
        return new SupplierResource($supplier->load('branch'));
    }

    #[OA\Put(path: '/suppliers/{id}', summary: 'Update a supplier', security: [['sanctum' => []]], tags: ['Suppliers'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(content: new OA\JsonContent(
            properties: [new OA\Property(property: 'name', type: 'string'), new OA\Property(property: 'phone', type: 'string')]
        )),
        responses: [new OA\Response(response: 200, description: 'Updated')]
    )]
    public function update(UpdateSupplierRequest $request, Supplier $supplier): SupplierResource
    {
        $supplier->update([...$request->validated(), 'updated_by' => $request->user()->id]);
        return new SupplierResource($supplier->fresh()->load('branch'));
    }

    #[OA\Delete(path: '/suppliers/{id}', summary: 'Delete a supplier', security: [['sanctum' => []]], tags: ['Suppliers'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 204, description: 'Deleted')]
    )]
    public function destroy(Request $request, Supplier $supplier): JsonResponse
    {
        $this->authorize('delete', $supplier);
        $supplier->update(['deleted_by' => $request->user()->id]);
        $supplier->delete();
        return response()->json(null, 204);
    }
}
