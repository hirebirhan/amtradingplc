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

final class CustomerController extends Controller
{
    public function index(Request $request): ResourceCollection
    {
        $this->authorize('viewAny', Customer::class);

        $query = Customer::query();

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

        return CustomerResource::collection(
            $query->orderBy('name')->paginate($request->integer('per_page', 20))
        );
    }

    public function store(StoreCustomerRequest $request)
    {
        $customer = Customer::create([
            ...$request->validated(),
            'created_by' => $request->user()->id,
        ]);

        return (new CustomerResource($customer))->response()->setStatusCode(201);
    }

    public function show(Customer $customer): CustomerResource
    {
        $this->authorize('view', $customer);
        return new CustomerResource($customer->load('branch'));
    }

    public function update(UpdateCustomerRequest $request, Customer $customer): CustomerResource
    {
        $customer->update([
            ...$request->validated(),
            'updated_by' => $request->user()->id,
        ]);

        return new CustomerResource($customer->fresh()->load('branch'));
    }

    public function destroy(Request $request, Customer $customer): JsonResponse
    {
        $this->authorize('delete', $customer);
        $customer->update(['deleted_by' => $request->user()->id]);
        $customer->delete();
        return response()->json(null, 204);
    }
}
