<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Users\StoreUserRequest;
use App\Http\Requests\Api\V1\Users\UpdateUserRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use App\Support\Access\UserAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Facades\Hash;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Users')]
final class UserController extends Controller
{
    #[OA\Get(path: '/users', summary: 'List users', security: [['sanctum' => []]], tags: ['Users'],
        parameters: [
            new OA\Parameter(name: 'filter[branch_id]', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'search', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', default: 20)),
        ],
        responses: [new OA\Response(response: 200, description: 'User list')]
    )]
    public function index(Request $request): ResourceCollection
    {
        $this->authorize('viewAny', User::class);
        $query = UserAccess::scopeToLocation(User::with('roles', 'branch', 'warehouse'), $request->user());
        if ($branchId = $request->integer('filter.branch_id') ?: null) {
            $query->where('branch_id', $branchId);
        }
        if ($search = $request->string('search')->trim()) {
            $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"));
        }

        return UserResource::collection($query->orderBy('name')->paginate($request->integer('per_page', 20)));
    }

    #[OA\Post(path: '/users', summary: 'Create a user', security: [['sanctum' => []]], tags: ['Users'],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['name', 'email', 'password'],
            properties: [
                new OA\Property(property: 'name', type: 'string'),
                new OA\Property(property: 'email', type: 'string', format: 'email'),
                new OA\Property(property: 'password', type: 'string', format: 'password'),
                new OA\Property(property: 'branch_id', type: 'integer'),
                new OA\Property(property: 'roles', type: 'array', items: new OA\Items(type: 'string')),
            ]
        )),
        responses: [new OA\Response(response: 201, description: 'User created')]
    )]
    public function store(StoreUserRequest $request)
    {
        $data = $request->validated();
        $roles = $data['roles'] ?? [];
        unset($data['roles']);
        $user = User::create([...$data, 'password' => Hash::make($data['password']), 'created_by' => $request->user()->id]);
        if ($roles) {
            $user->assignRole($roles);
        }

        return (new UserResource($user->load('roles', 'branch', 'warehouse')))->response()->setStatusCode(201);
    }

    #[OA\Get(path: '/users/{id}', summary: 'Get a user', security: [['sanctum' => []]], tags: ['Users'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'User detail')]
    )]
    public function show(User $user): UserResource
    {
        $this->authorize('view', $user);

        return new UserResource($user->load('roles', 'branch', 'warehouse'));
    }

    #[OA\Put(path: '/users/{id}', summary: 'Update a user', security: [['sanctum' => []]], tags: ['Users'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(content: new OA\JsonContent(
            properties: [new OA\Property(property: 'name', type: 'string'), new OA\Property(property: 'is_active', type: 'boolean')]
        )),
        responses: [new OA\Response(response: 200, description: 'Updated')]
    )]
    public function update(UpdateUserRequest $request, User $user): UserResource
    {
        $data = $request->validated();
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }
        $user->update([...$data, 'updated_by' => $request->user()->id]);

        return new UserResource($user->fresh()->load('roles', 'branch', 'warehouse'));
    }

    #[OA\Delete(path: '/users/{id}', summary: 'Delete a user', security: [['sanctum' => []]], tags: ['Users'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 204, description: 'Deleted')]
    )]
    public function destroy(Request $request, User $user): JsonResponse
    {
        $this->authorize('delete', $user);
        $user->update(['deleted_by' => $request->user()->id]);
        $user->delete();

        return response()->json(null, 204);
    }

    #[OA\Get(path: '/users/{id}/roles', summary: "Get a user's roles", security: [['sanctum' => []]], tags: ['Users'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Role names')]
    )]
    public function roles(User $user): JsonResponse
    {
        $this->authorize('view', $user);

        return response()->json(['data' => $user->getRoleNames()]);
    }

    #[OA\Post(path: '/users/{id}/roles', summary: 'Sync roles for a user', security: [['sanctum' => []]], tags: ['Users'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['roles'],
            properties: [new OA\Property(property: 'roles', type: 'array', items: new OA\Items(type: 'string'))]
        )),
        responses: [new OA\Response(response: 200, description: 'Roles synced')]
    )]
    public function syncRoles(Request $request, User $user): JsonResponse
    {
        $this->authorize('update', $user);
        $request->validate(['roles' => ['required', 'array'], 'roles.*' => ['string', 'exists:roles,name']]);
        $user->syncRoles($request->input('roles'));

        return response()->json(['data' => $user->fresh()->getRoleNames()]);
    }
}
