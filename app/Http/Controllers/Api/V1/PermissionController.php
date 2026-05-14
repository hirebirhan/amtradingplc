<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;

final class PermissionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', \App\Models\Role::class);

        $permissions = Permission::orderBy('name')->pluck('name');
        return response()->json(['data' => $permissions]);
    }
}
