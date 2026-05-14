<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Users\StoreUserRequest;
use App\Http\Requests\Api\V1\Users\UpdateUserRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

final class UserController extends Controller
{
    public function index(Request $request): ResourceCollection
    {
        $this->authorize('viewAny', User::class);

        $query = User::with('roles', 'branch', 'warehouse');

        if ($branchId = $request->integer('filter.branch_id') ?: null) {
            $query->where('branch_id', $branchId);
        }

        if ($search = $request->string('search')->trim()) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        return UserResource::collection(
            $query->orderBy('name')->paginate($request->integer('per_page', 20))
        );
    }

    public function store(StoreUserRequest $request)
    {
        $data = $request->validated();
        $roles = $data['roles'] ?? [];
        unset($data['roles']);

        $user = User::create([
            ...$data,
            'password'   => Hash::make($data['password']),
            'created_by' => $request->user()->id,
        ]);

        if ($roles) {
            $user->assignRole($roles);
        }

        return (new UserResource($user->load('roles', 'branch', 'warehouse')))->response()->setStatusCode(201);
    }

    public function show(User $user): UserResource
    {
        $this->authorize('view', $user);
        return new UserResource($user->load('roles', 'branch', 'warehouse'));
    }

    public function update(UpdateUserRequest $request, User $user): UserResource
    {
        $data = $request->validated();

        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        $user->update([...$data, 'updated_by' => $request->user()->id]);

        return new UserResource($user->fresh()->load('roles', 'branch', 'warehouse'));
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        $this->authorize('delete', $user);
        $user->update(['deleted_by' => $request->user()->id]);
        $user->delete();
        return response()->json(null, 204);
    }

    public function roles(User $user): JsonResponse
    {
        $this->authorize('view', $user);
        return response()->json(['data' => $user->getRoleNames()]);
    }

    public function syncRoles(Request $request, User $user): JsonResponse
    {
        $this->authorize('update', $user);
        $request->validate([
            'roles'   => ['required', 'array'],
            'roles.*' => ['string', 'exists:roles,name'],
        ]);

        $user->syncRoles($request->input('roles'));
        return response()->json(['data' => $user->fresh()->getRoleNames()]);
    }
}
