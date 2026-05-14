<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\BankAccounts\StoreBankAccountRequest;
use App\Http\Requests\Api\V1\BankAccounts\UpdateBankAccountRequest;
use App\Http\Resources\Api\V1\BankAccountResource;
use App\Models\BankAccount;
use App\Support\Access\UserAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'BankAccounts')]
final class BankAccountController extends Controller
{
    #[OA\Get(path: '/bank-accounts', summary: 'List bank accounts', security: [['sanctum' => []]], tags: ['BankAccounts'],
        parameters: [
            new OA\Parameter(name: 'filter[branch_id]', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'active_only', in: 'query', schema: new OA\Schema(type: 'boolean')),
        ],
        responses: [new OA\Response(response: 200, description: 'Bank account list')]
    )]
    public function index(Request $request): ResourceCollection
    {
        $this->authorize('viewAny', BankAccount::class);
        $query = UserAccess::scopeToLocation(BankAccount::with('branch', 'warehouse'), $request->user());
        if ($branchId = $request->integer('filter.branch_id') ?: null) {
            $query->where('branch_id', $branchId);
        }
        if ($request->boolean('active_only')) {
            $query->where('is_active', true);
        }

        return BankAccountResource::collection($query->orderBy('account_name')->paginate($request->integer('per_page', 20)));
    }

    #[OA\Post(path: '/bank-accounts', summary: 'Create a bank account', security: [['sanctum' => []]], tags: ['BankAccounts'],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['bank_name', 'account_name', 'account_number'],
            properties: [
                new OA\Property(property: 'bank_name', type: 'string'),
                new OA\Property(property: 'account_name', type: 'string'),
                new OA\Property(property: 'account_number', type: 'string'),
                new OA\Property(property: 'branch_id', type: 'integer'),
                new OA\Property(property: 'is_active', type: 'boolean'),
            ]
        )),
        responses: [new OA\Response(response: 201, description: 'Created')]
    )]
    public function store(StoreBankAccountRequest $request): BankAccountResource
    {
        $account = BankAccount::create($request->validated());

        return (new BankAccountResource($account->load('branch', 'warehouse')))->response()->setStatusCode(201);
    }

    #[OA\Get(path: '/bank-accounts/{id}', summary: 'Get a bank account', security: [['sanctum' => []]], tags: ['BankAccounts'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Bank account detail')]
    )]
    public function show(BankAccount $bankAccount): BankAccountResource
    {
        $this->authorize('view', $bankAccount);

        return new BankAccountResource($bankAccount->load('branch', 'warehouse'));
    }

    #[OA\Put(path: '/bank-accounts/{id}', summary: 'Update a bank account', security: [['sanctum' => []]], tags: ['BankAccounts'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(content: new OA\JsonContent(
            properties: [new OA\Property(property: 'bank_name', type: 'string'), new OA\Property(property: 'is_active', type: 'boolean')]
        )),
        responses: [new OA\Response(response: 200, description: 'Updated')]
    )]
    public function update(UpdateBankAccountRequest $request, BankAccount $bankAccount): BankAccountResource
    {
        $bankAccount->update($request->validated());

        return new BankAccountResource($bankAccount->fresh()->load('branch', 'warehouse'));
    }

    #[OA\Delete(path: '/bank-accounts/{id}', summary: 'Delete a bank account', security: [['sanctum' => []]], tags: ['BankAccounts'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 204, description: 'Deleted')]
    )]
    public function destroy(Request $request, BankAccount $bankAccount): JsonResponse
    {
        $this->authorize('delete', $bankAccount);
        $bankAccount->delete();

        return response()->json(null, 204);
    }
}
