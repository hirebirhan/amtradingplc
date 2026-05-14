<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Roles\StoreRoleRequest;
use App\Http\Resources\Api\V1\RoleResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

final class RoleController extends Controller
{
    public function index(Request $request): ResourceCollection
    {
        $this->authorize('viewAny', \App\Models\Role::class);
        return RoleResource::collection(Role::with('permissions')->get());
    }

    public function store(StoreRoleRequest $request): RoleResource
    {
        $role = Role::create(['name' => $request->input('name'), 'guard_name' => 'web']);

        if ($permissions = $request->input('permissions', [])) {
            $role->syncPermissions($permissions);
        }

        return (new RoleResource($role->load('permissions')))->response()->setStatusCode(201);
    }

    public function show(Role $role): RoleResource
    {
        $this->authorize('viewAny', \App\Models\Role::class);
        return new RoleResource($role->load('permissions'));
    }

    public function update(Request $request, Role $role): RoleResource
    {
        $this->authorize('viewAny', \App\Models\Role::class);
        $request->validate([
            'permissions'   => ['required', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        $role->syncPermissions($request->input('permissions'));
        return new RoleResource($role->fresh()->load('permissions'));
    }

    public function destroy(Role $role): JsonResponse
    {
        $this->authorize('viewAny', \App\Models\Role::class);
        $role->delete();
        return response()->json(null, 204);
    }
}
