<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Customers\StoreCustomerRequest;
use App\Http\Requests\Api\V1\Customers\UpdateCustomerRequest;
use App\Http\Resources\Api\V1\CustomerResource;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Customers')]
final class CustomerController extends Controller
{
    #[OA\Get(path: '/customers', summary: 'List customers', security: [['sanctum' => []]], tags: ['Customers'],
        parameters: [
            new OA\Parameter(name: 'filter[branch_id]', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'search', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', default: 20)),
        ],
        responses: [new OA\Response(response: 200, description: 'Customer list')]
    )]
    public function index(Request $request): ResourceCollection
    {
        $this->authorize('viewAny', Customer::class);
        $query = Customer::query();
        if ($branchId = $request->integer('filter.branch_id') ?: null) { $query->where('branch_id', $branchId); }
        if ($search = $request->string('search')->trim()) {
            $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"));
        }
        return CustomerResource::collection($query->orderBy('name')->paginate($request->integer('per_page', 20)));
    }

    #[OA\Post(path: '/customers', summary: 'Create a customer', security: [['sanctum' => []]], tags: ['Customers'],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['name'],
            properties: [
                new OA\Property(property: 'name', type: 'string'),
                new OA\Property(property: 'email', type: 'string', format: 'email'),
                new OA\Property(property: 'phone', type: 'string'),
                new OA\Property(property: 'address', type: 'string'),
                new OA\Property(property: 'branch_id', type: 'integer'),
            ]
        )),
        responses: [new OA\Response(response: 201, description: 'Customer created')]
    )]
    public function store(StoreCustomerRequest $request)
    {
        $customer = Customer::create([...$request->validated(), 'created_by' => $request->user()->id]);
        return (new CustomerResource($customer))->response()->setStatusCode(201);
    }

    #[OA\Get(path: '/customers/{id}', summary: 'Get a customer', security: [['sanctum' => []]], tags: ['Customers'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Customer detail')]
    )]
    public function show(Customer $customer): CustomerResource
    {
        $this->authorize('view', $customer);
        return new CustomerResource($customer->load('branch'));
    }

    #[OA\Put(path: '/customers/{id}', summary: 'Update a customer', security: [['sanctum' => []]], tags: ['Customers'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(content: new OA\JsonContent(
            properties: [new OA\Property(property: 'name', type: 'string'), new OA\Property(property: 'phone', type: 'string')]
        )),
        responses: [new OA\Response(response: 200, description: 'Updated')]
    )]
    public function update(UpdateCustomerRequest $request, Customer $customer): CustomerResource
    {
        $customer->update([...$request->validated(), 'updated_by' => $request->user()->id]);
        return new CustomerResource($customer->fresh()->load('branch'));
    }

    #[OA\Delete(path: '/customers/{id}', summary: 'Delete a customer', security: [['sanctum' => []]], tags: ['Customers'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 204, description: 'Deleted')]
    )]
    public function destroy(Request $request, Customer $customer): JsonResponse
    {
        $this->authorize('delete', $customer);
        $customer->update(['deleted_by' => $request->user()->id]);
        $customer->delete();
        return response()->json(null, 204);
    }
}
