<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Branches\StoreBranchRequest;
use App\Http\Requests\Api\V1\Branches\UpdateBranchRequest;
use App\Http\Resources\Api\V1\BranchResource;
use App\Models\Branch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Branches')]
final class BranchController extends Controller
{
    #[OA\Get(
        path: '/branches',
        summary: 'List branches',
        security: [['sanctum' => []]],
        tags: ['Branches'],
        parameters: [
            new OA\Parameter(name: 'active_only', in: 'query', schema: new OA\Schema(type: 'boolean')),
            new OA\Parameter(name: 'search', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', default: 20)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paginated branch list', content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Branch')),
                    new OA\Property(property: 'meta', ref: '#/components/schemas/Pagination'),
                ]
            )),
        ]
    )]
    public function index(Request $request): ResourceCollection
    {
        $this->authorize('viewAny', Branch::class);

        $query = Branch::query();

        if ($request->boolean('active_only')) {
            $query->active();
        }

        if ($search = $request->string('search')->trim()) {
            $query->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
        }

        return BranchResource::collection(
            $query->ordered()->paginate($request->integer('per_page', 20))
        );
    }

    #[OA\Post(
        path: '/branches',
        summary: 'Create a branch',
        security: [['sanctum' => []]],
        tags: ['Branches'],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['name'],
            properties: [
                new OA\Property(property: 'name', type: 'string'),
                new OA\Property(property: 'address', type: 'string'),
                new OA\Property(property: 'phone', type: 'string'),
                new OA\Property(property: 'is_active', type: 'boolean'),
            ]
        )),
        responses: [
            new OA\Response(response: 201, description: 'Branch created', content: new OA\JsonContent(
                properties: [new OA\Property(property: 'data', ref: '#/components/schemas/Branch')]
            )),
            new OA\Response(response: 422, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')),
        ]
    )]
    public function store(StoreBranchRequest $request): BranchResource
    {
        $branch = Branch::create([
            ...$request->validated(),
            'created_by' => $request->user()->id,
        ]);

        return new BranchResource($branch);
    }

    #[OA\Get(
        path: '/branches/{id}',
        summary: 'Get a branch',
        security: [['sanctum' => []]],
        tags: ['Branches'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Branch detail', content: new OA\JsonContent(
                properties: [new OA\Property(property: 'data', ref: '#/components/schemas/Branch')]
            )),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function show(Branch $branch): BranchResource
    {
        $this->authorize('view', $branch);
        return new BranchResource($branch->load('warehouses'));
    }

    #[OA\Put(
        path: '/branches/{id}',
        summary: 'Update a branch',
        security: [['sanctum' => []]],
        tags: ['Branches'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'name', type: 'string'),
                new OA\Property(property: 'address', type: 'string'),
                new OA\Property(property: 'phone', type: 'string'),
                new OA\Property(property: 'is_active', type: 'boolean'),
            ]
        )),
        responses: [
            new OA\Response(response: 200, description: 'Branch updated', content: new OA\JsonContent(
                properties: [new OA\Property(property: 'data', ref: '#/components/schemas/Branch')]
            )),
        ]
    )]
    public function update(UpdateBranchRequest $request, Branch $branch): BranchResource
    {
        $branch->update([
            ...$request->validated(),
            'updated_by' => $request->user()->id,
        ]);

        return new BranchResource($branch->fresh()->load('warehouses'));
    }

    #[OA\Delete(
        path: '/branches/{id}',
        summary: 'Delete a branch',
        security: [['sanctum' => []]],
        tags: ['Branches'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 204, description: 'Deleted'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function destroy(Request $request, Branch $branch): JsonResponse
    {
        $this->authorize('delete', $branch);
        $branch->update(['deleted_by' => $request->user()->id]);
        $branch->delete();
        return response()->json(null, 204);
    }
}
