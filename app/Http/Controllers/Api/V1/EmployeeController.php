<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Employees\StoreEmployeeRequest;
use App\Http\Requests\Api\V1\Employees\UpdateEmployeeRequest;
use App\Http\Resources\Api\V1\EmployeeResource;
use App\Models\Employee;
use App\Support\Access\UserAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Employees')]
final class EmployeeController extends Controller
{
    #[OA\Get(path: '/employees', summary: 'List employees', security: [['sanctum' => []]], tags: ['Employees'],
        parameters: [
            new OA\Parameter(name: 'filter[branch_id]', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'search', in: 'query', schema: new OA\Schema(type: 'string')),
        ],
        responses: [new OA\Response(response: 200, description: 'Employee list')]
    )]
    public function index(Request $request): ResourceCollection
    {
        $this->authorize('viewAny', Employee::class);
        $query = UserAccess::scopeToLocation(Employee::with('branch', 'warehouse'), $request->user());
        if ($branchId = $request->integer('filter.branch_id') ?: null) {
            $query->where('branch_id', $branchId);
        }
        if ($search = $request->string('search')->trim()) {
            $query->where(fn ($q) => $q->where('first_name', 'like', "%{$search}%")->orWhere('last_name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"));
        }

        return EmployeeResource::collection($query->orderBy('first_name')->paginate($request->integer('per_page', 20)));
    }

    #[OA\Post(path: '/employees', summary: 'Create an employee', security: [['sanctum' => []]], tags: ['Employees'],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['first_name', 'last_name'],
            properties: [
                new OA\Property(property: 'first_name', type: 'string'),
                new OA\Property(property: 'last_name', type: 'string'),
                new OA\Property(property: 'email', type: 'string', format: 'email'),
                new OA\Property(property: 'phone', type: 'string'),
                new OA\Property(property: 'branch_id', type: 'integer'),
            ]
        )),
        responses: [new OA\Response(response: 201, description: 'Created')]
    )]
    public function store(StoreEmployeeRequest $request)
    {
        $employee = Employee::create($request->validated());

        return (new EmployeeResource($employee->load('branch', 'warehouse')))->response()->setStatusCode(201);
    }

    #[OA\Get(path: '/employees/{id}', summary: 'Get an employee', security: [['sanctum' => []]], tags: ['Employees'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Employee detail')]
    )]
    public function show(Employee $employee): EmployeeResource
    {
        $this->authorize('view', $employee);

        return new EmployeeResource($employee->load('branch', 'warehouse'));
    }

    #[OA\Put(path: '/employees/{id}', summary: 'Update an employee', security: [['sanctum' => []]], tags: ['Employees'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(content: new OA\JsonContent(
            properties: [new OA\Property(property: 'first_name', type: 'string'), new OA\Property(property: 'last_name', type: 'string')]
        )),
        responses: [new OA\Response(response: 200, description: 'Updated')]
    )]
    public function update(UpdateEmployeeRequest $request, Employee $employee): EmployeeResource
    {
        $employee->update($request->validated());

        return new EmployeeResource($employee->fresh()->load('branch', 'warehouse'));
    }

    #[OA\Delete(path: '/employees/{id}', summary: 'Delete an employee', security: [['sanctum' => []]], tags: ['Employees'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 204, description: 'Deleted')]
    )]
    public function destroy(Request $request, Employee $employee): JsonResponse
    {
        $this->authorize('delete', $employee);
        $employee->delete();

        return response()->json(null, 204);
    }
}
