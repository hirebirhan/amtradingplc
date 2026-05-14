<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Expenses\StoreExpenseRequest;
use App\Http\Resources\Api\V1\ExpenseResource;
use App\Models\Expense;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Expenses')]
final class ExpenseController extends Controller
{
    #[OA\Get(path: '/expenses', summary: 'List expenses', security: [['sanctum' => []]], tags: ['Expenses'],
        parameters: [
            new OA\Parameter(name: 'filter[branch_id]', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'filter[date_from]', in: 'query', schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'filter[date_to]', in: 'query', schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', default: 20)),
        ],
        responses: [new OA\Response(response: 200, description: 'Expense list')]
    )]
    public function index(Request $request): ResourceCollection
    {
        $this->authorize('viewAny', Expense::class);
        $query = Expense::with('branch');
        if ($branchId = $request->integer('filter.branch_id') ?: null) { $query->where('branch_id', $branchId); }
        if ($from = $request->input('filter.date_from')) { $query->whereDate('expense_date', '>=', $from); }
        if ($to = $request->input('filter.date_to')) { $query->whereDate('expense_date', '<=', $to); }
        return ExpenseResource::collection($query->latest('expense_date')->paginate($request->integer('per_page', 20)));
    }

    #[OA\Post(path: '/expenses', summary: 'Create an expense', security: [['sanctum' => []]], tags: ['Expenses'],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['category', 'amount', 'expense_date'],
            properties: [
                new OA\Property(property: 'category', type: 'string'),
                new OA\Property(property: 'amount', type: 'number', minimum: 0.01),
                new OA\Property(property: 'expense_date', type: 'string', format: 'date'),
                new OA\Property(property: 'payment_method', type: 'string'),
                new OA\Property(property: 'branch_id', type: 'integer'),
                new OA\Property(property: 'note', type: 'string'),
                new OA\Property(property: 'is_recurring', type: 'boolean'),
            ]
        )),
        responses: [new OA\Response(response: 201, description: 'Created')]
    )]
    public function store(StoreExpenseRequest $request)
    {
        $expense = Expense::create([...$request->validated(), 'user_id' => $request->user()->id]);
        return (new ExpenseResource($expense->load('branch')))->response()->setStatusCode(201);
    }

    #[OA\Get(path: '/expenses/{id}', summary: 'Get an expense', security: [['sanctum' => []]], tags: ['Expenses'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Expense detail')]
    )]
    public function show(Request $request, Expense $expense): ExpenseResource
    {
        $this->authorize('view', $expense);
        return new ExpenseResource($expense->load('branch'));
    }

    #[OA\Put(path: '/expenses/{id}', summary: 'Update an expense', security: [['sanctum' => []]], tags: ['Expenses'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(content: new OA\JsonContent(
            properties: [new OA\Property(property: 'amount', type: 'number'), new OA\Property(property: 'note', type: 'string')]
        )),
        responses: [new OA\Response(response: 200, description: 'Updated')]
    )]
    public function update(Request $request, Expense $expense): ExpenseResource
    {
        $this->authorize('update', $expense);
        $request->validate(['amount' => ['sometimes', 'numeric', 'min:0.01'], 'expense_date' => ['sometimes', 'date'], 'note' => ['nullable', 'string']]);
        $expense->update($request->validated());
        return new ExpenseResource($expense->fresh()->load('branch'));
    }

    #[OA\Delete(path: '/expenses/{id}', summary: 'Delete an expense', security: [['sanctum' => []]], tags: ['Expenses'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 204, description: 'Deleted')]
    )]
    public function destroy(Request $request, Expense $expense): JsonResponse
    {
        $this->authorize('delete', $expense);
        $expense->delete();
        return response()->json(null, 204);
    }
}
