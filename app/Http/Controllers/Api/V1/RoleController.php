<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Roles\StoreRoleRequest;
use App\Http\Resources\Api\V1\RoleResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use OpenApi\Attributes as OA;
use Spatie\Permission\Models\Role;

#[OA\Tag(name: 'Roles')]
final class RoleController extends Controller
{
    #[OA\Get(path: '/roles', summary: 'List roles with permissions', security: [['sanctum' => []]], tags: ['Roles'],
        responses: [new OA\Response(response: 200, description: 'Roles')]
    )]
    public function index(Request $request): ResourceCollection
    {
        $this->authorize('viewAny', \App\Models\Role::class);
        return RoleResource::collection(Role::with('permissions')->get());
    }

    #[OA\Post(path: '/roles', summary: 'Create a role', security: [['sanctum' => []]], tags: ['Roles'],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['name'],
            properties: [
                new OA\Property(property: 'name', type: 'string', example: 'Accountant'),
                new OA\Property(property: 'permissions', type: 'array', items: new OA\Items(type: 'string')),
            ]
        )),
        responses: [new OA\Response(response: 201, description: 'Role created')]
    )]
    public function store(StoreRoleRequest $request): RoleResource
    {
        $role = Role::create(['name' => $request->input('name'), 'guard_name' => 'web']);
        if ($permissions = $request->input('permissions', [])) { $role->syncPermissions($permissions); }
        return (new RoleResource($role->load('permissions')))->response()->setStatusCode(201);
    }

    #[OA\Get(path: '/roles/{id}', summary: 'Get a role', security: [['sanctum' => []]], tags: ['Roles'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Role detail')]
    )]
    public function show(Role $role): RoleResource
    {
        $this->authorize('viewAny', \App\Models\Role::class);
        return new RoleResource($role->load('permissions'));
    }

    #[OA\Put(path: '/roles/{id}', summary: 'Sync role permissions', security: [['sanctum' => []]], tags: ['Roles'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['permissions'],
            properties: [new OA\Property(property: 'permissions', type: 'array', items: new OA\Items(type: 'string'))]
        )),
        responses: [new OA\Response(response: 200, description: 'Permissions synced')]
    )]
    public function update(Request $request, Role $role): RoleResource
    {
        $this->authorize('viewAny', \App\Models\Role::class);
        $request->validate(['permissions' => ['required', 'array'], 'permissions.*' => ['string', 'exists:permissions,name']]);
        $role->syncPermissions($request->input('permissions'));
        return new RoleResource($role->fresh()->load('permissions'));
    }

    #[OA\Delete(path: '/roles/{id}', summary: 'Delete a role', security: [['sanctum' => []]], tags: ['Roles'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 204, description: 'Deleted')]
    )]
    public function destroy(Role $role): JsonResponse
    {
        $this->authorize('viewAny', \App\Models\Role::class);
        $role->delete();
        return response()->json(null, 204);
    }
}
