<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Employees\StoreEmployeeRequest;
use App\Http\Requests\Api\V1\Employees\UpdateEmployeeRequest;
use App\Http\Resources\Api\V1\EmployeeResource;
use App\Models\Employee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

final class EmployeeController extends Controller
{
    public function index(Request $request): ResourceCollection
    {
        $this->authorize('viewAny', Employee::class);

        $query = Employee::with('branch', 'warehouse');

        if ($branchId = $request->integer('filter.branch_id') ?: null) {
            $query->where('branch_id', $branchId);
        }

        if ($search = $request->string('search')->trim()) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        return EmployeeResource::collection(
            $query->orderBy('first_name')->paginate($request->integer('per_page', 20))
        );
    }

    public function store(StoreEmployeeRequest $request)
    {
        $employee = Employee::create($request->validated());
        return (new EmployeeResource($employee->load('branch', 'warehouse')))->response()->setStatusCode(201);
    }

    public function show(Employee $employee): EmployeeResource
    {
        $this->authorize('view', $employee);
        return new EmployeeResource($employee->load('branch', 'warehouse'));
    }

    public function update(UpdateEmployeeRequest $request, Employee $employee): EmployeeResource
    {
        $employee->update($request->validated());
        return new EmployeeResource($employee->fresh()->load('branch', 'warehouse'));
    }

    public function destroy(Request $request, Employee $employee): JsonResponse
    {
        $this->authorize('delete', $employee);
        $employee->delete();
        return response()->json(null, 204);
    }
}
