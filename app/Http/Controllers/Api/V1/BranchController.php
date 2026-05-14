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

final class BranchController extends Controller
{
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

    public function store(StoreBranchRequest $request): BranchResource
    {
        $branch = Branch::create([
            ...$request->validated(),
            'created_by' => $request->user()->id,
        ]);

        return new BranchResource($branch);
    }

    public function show(Branch $branch): BranchResource
    {
        $this->authorize('view', $branch);
        return new BranchResource($branch->load('warehouses'));
    }

    public function update(UpdateBranchRequest $request, Branch $branch): BranchResource
    {
        $branch->update([
            ...$request->validated(),
            'updated_by' => $request->user()->id,
        ]);

        return new BranchResource($branch->fresh()->load('warehouses'));
    }

    public function destroy(Request $request, Branch $branch): JsonResponse
    {
        $this->authorize('delete', $branch);
        $branch->update(['deleted_by' => $request->user()->id]);
        $branch->delete();
        return response()->json(null, 204);
    }
}
