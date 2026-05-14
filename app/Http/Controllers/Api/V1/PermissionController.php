<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;
use Spatie\Permission\Models\Permission;

#[OA\Tag(name: 'Roles')]
final class PermissionController extends Controller
{
    #[OA\Get(path: '/permissions', summary: 'List all permission names', security: [['sanctum' => []]], tags: ['Roles'],
        responses: [
            new OA\Response(response: 200, description: 'Permission list', content: new OA\JsonContent(
                properties: [new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'string'))]
            )),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', \App\Models\Role::class);
        $permissions = Permission::orderBy('name')->pluck('name');
        return response()->json(['data' => $permissions]);
    }
}
