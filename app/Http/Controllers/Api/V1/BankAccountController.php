<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\BankAccounts\StoreBankAccountRequest;
use App\Http\Requests\Api\V1\BankAccounts\UpdateBankAccountRequest;
use App\Http\Resources\Api\V1\BankAccountResource;
use App\Models\BankAccount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

final class BankAccountController extends Controller
{
    public function index(Request $request): ResourceCollection
    {
        $this->authorize('viewAny', BankAccount::class);

        $query = BankAccount::with('branch', 'warehouse');

        if ($branchId = $request->integer('filter.branch_id') ?: null) {
            $query->where('branch_id', $branchId);
        }

        if ($request->boolean('active_only')) {
            $query->where('is_active', true);
        }

        return BankAccountResource::collection(
            $query->orderBy('account_name')->paginate($request->integer('per_page', 20))
        );
    }

    public function store(StoreBankAccountRequest $request): BankAccountResource
    {
        $account = BankAccount::create($request->validated());
        return (new BankAccountResource($account->load('branch', 'warehouse')))->response()->setStatusCode(201);
    }

    public function show(BankAccount $bankAccount): BankAccountResource
    {
        $this->authorize('view', $bankAccount);
        return new BankAccountResource($bankAccount->load('branch', 'warehouse'));
    }

    public function update(UpdateBankAccountRequest $request, BankAccount $bankAccount): BankAccountResource
    {
        $bankAccount->update($request->validated());
        return new BankAccountResource($bankAccount->fresh()->load('branch', 'warehouse'));
    }

    public function destroy(Request $request, BankAccount $bankAccount): JsonResponse
    {
        $this->authorize('delete', $bankAccount);
        $bankAccount->delete();
        return response()->json(null, 204);
    }
}
