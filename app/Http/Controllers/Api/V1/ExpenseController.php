<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Expenses\StoreExpenseRequest;
use App\Http\Resources\Api\V1\ExpenseResource;
use App\Models\Expense;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

final class ExpenseController extends Controller
{
    public function index(Request $request): ResourceCollection
    {
        $this->authorize('viewAny', Expense::class);

        $query = Expense::with('branch');

        if ($branchId = $request->integer('filter.branch_id') ?: null) {
            $query->where('branch_id', $branchId);
        }
        if ($from = $request->input('filter.date_from')) {
            $query->whereDate('expense_date', '>=', $from);
        }
        if ($to = $request->input('filter.date_to')) {
            $query->whereDate('expense_date', '<=', $to);
        }

        return ExpenseResource::collection(
            $query->latest('expense_date')->paginate($request->integer('per_page', 20))
        );
    }

    public function store(StoreExpenseRequest $request)
    {
        $expense = Expense::create([
            ...$request->validated(),
            'user_id' => $request->user()->id,
        ]);

        return (new ExpenseResource($expense->load('branch')))->response()->setStatusCode(201);
    }

    public function show(Request $request, Expense $expense): ExpenseResource
    {
        $this->authorize('view', $expense);
        return new ExpenseResource($expense->load('branch'));
    }

    public function update(Request $request, Expense $expense): ExpenseResource
    {
        $this->authorize('update', $expense);
        $request->validate([
            'amount'         => ['sometimes', 'numeric', 'min:0.01'],
            'expense_date'   => ['sometimes', 'date'],
            'note'           => ['nullable', 'string'],
        ]);
        $expense->update($request->validated());
        return new ExpenseResource($expense->fresh()->load('branch'));
    }

    public function destroy(Request $request, Expense $expense): JsonResponse
    {
        $this->authorize('delete', $expense);
        $expense->delete();
        return response()->json(null, 204);
    }
}
