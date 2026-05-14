<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Credit;
use App\Models\Expense;
use App\Models\Item;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\Stock;
use App\Support\Access\UserAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Reports')]
final class ReportController extends Controller
{
    #[OA\Get(path: '/reports/summary', summary: 'Overall summary counts', security: [['sanctum' => []]], tags: ['Reports'],
        responses: [new OA\Response(response: 200, description: 'Summary', content: new OA\JsonContent(
            properties: [new OA\Property(property: 'data', type: 'object', properties: [
                new OA\Property(property: 'total_items', type: 'integer'),
                new OA\Property(property: 'total_sales', type: 'integer'),
                new OA\Property(property: 'total_purchases', type: 'integer'),
                new OA\Property(property: 'total_credits', type: 'number'),
                new OA\Property(property: 'total_expenses', type: 'number'),
            ])]
        ))]
    )]
    public function summary(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('reports.view'), 403);
        $user = $request->user();

        return response()->json(['data' => [
            'total_items' => Item::count(),
            'total_sales' => UserAccess::scopeToLocation(Sale::query(), $user)->count(),
            'total_purchases' => UserAccess::scopeToLocation(Purchase::query(), $user)->count(),
            'total_credits' => UserAccess::scopeToLocation(Credit::query(), $user)->sum('balance'),
            'total_expenses' => UserAccess::scopeToBranches(Expense::query(), $user)->sum('amount'),
        ]]);
    }

    #[OA\Get(path: '/reports/inventory', summary: 'Inventory report', security: [['sanctum' => []]], tags: ['Reports'],
        parameters: [
            new OA\Parameter(name: 'branch_id', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'warehouse_id', in: 'query', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [new OA\Response(response: 200, description: 'Inventory data')]
    )]
    public function inventory(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('reports.view'), 403);
        $branchId = $request->integer('branch_id') ?: null;
        $warehouseId = $request->integer('warehouse_id') ?: null;
        $query = UserAccess::scopeToWarehouses(Stock::with('item.category', 'warehouse', 'branch'), $request->user());
        if ($branchId) {
            $query->where('branch_id', $branchId);
        }
        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        return response()->json(['data' => $query->get()]);
    }

    #[OA\Get(path: '/reports/sales', summary: 'Sales summary report', security: [['sanctum' => []]], tags: ['Reports'],
        parameters: [
            new OA\Parameter(name: 'date_from', in: 'query', schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'date_to', in: 'query', schema: new OA\Schema(type: 'string', format: 'date')),
        ],
        responses: [new OA\Response(response: 200, description: 'Sales totals', content: new OA\JsonContent(
            properties: [new OA\Property(property: 'data', type: 'object', properties: [
                new OA\Property(property: 'count', type: 'integer'),
                new OA\Property(property: 'total_revenue', type: 'number'),
                new OA\Property(property: 'paid', type: 'number'),
                new OA\Property(property: 'outstanding', type: 'number'),
            ])]
        ))]
    )]
    public function sales(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('reports.view'), 403);
        $from = $request->input('date_from', now()->startOfMonth()->toDateString());
        $to = $request->input('date_to', now()->toDateString());
        $data = UserAccess::scopeToLocation(Sale::query(), $request->user())->whereBetween('sale_date', [$from, $to])
            ->selectRaw('COUNT(*) as count, SUM(total_amount) as total_revenue, SUM(paid_amount) as paid, SUM(due_amount) as outstanding')->first();

        return response()->json(['data' => $data]);
    }

    #[OA\Get(path: '/reports/purchases', summary: 'Purchases summary report', security: [['sanctum' => []]], tags: ['Reports'],
        parameters: [
            new OA\Parameter(name: 'date_from', in: 'query', schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'date_to', in: 'query', schema: new OA\Schema(type: 'string', format: 'date')),
        ],
        responses: [new OA\Response(response: 200, description: 'Purchase totals')]
    )]
    public function purchases(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('reports.view'), 403);
        $from = $request->input('date_from', now()->startOfMonth()->toDateString());
        $to = $request->input('date_to', now()->toDateString());
        $data = UserAccess::scopeToLocation(Purchase::query(), $request->user())->whereBetween('purchase_date', [$from, $to])
            ->selectRaw('COUNT(*) as count, SUM(total_amount) as total_cost, SUM(paid_amount) as paid, SUM(due_amount) as outstanding')->first();

        return response()->json(['data' => $data]);
    }

    #[OA\Get(path: '/reports/financial', summary: 'Financial P&L report (finance roles only)', security: [['sanctum' => []]], tags: ['Reports'],
        parameters: [
            new OA\Parameter(name: 'date_from', in: 'query', schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'date_to', in: 'query', schema: new OA\Schema(type: 'string', format: 'date')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'P&L data', content: new OA\JsonContent(
                properties: [new OA\Property(property: 'data', type: 'object', properties: [
                    new OA\Property(property: 'revenue', type: 'number'),
                    new OA\Property(property: 'costs', type: 'number'),
                    new OA\Property(property: 'expenses', type: 'number'),
                    new OA\Property(property: 'gross_profit', type: 'number'),
                    new OA\Property(property: 'net_profit', type: 'number'),
                ])]
            )),
            new OA\Response(response: 403, description: 'Insufficient role'),
        ]
    )]
    public function financial(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('reports.view'), 403);
        abort_unless(
            $request->user()->isSuperAdmin() || $request->user()->isGeneralManager() || $request->user()->hasRole('Accountant'),
            403, 'Financial reports require a finance role.'
        );
        $from = $request->input('date_from', now()->startOfMonth()->toDateString());
        $to = $request->input('date_to', now()->toDateString());
        $revenue = UserAccess::scopeToLocation(Sale::query(), $request->user())
            ->whereBetween('sale_date', [$from, $to])
            ->sum('paid_amount');
        $costs = UserAccess::scopeToLocation(Purchase::query(), $request->user())
            ->whereBetween('purchase_date', [$from, $to])
            ->sum('total_amount');
        $expenses = UserAccess::scopeToBranches(Expense::query(), $request->user())
            ->whereBetween('expense_date', [$from, $to])
            ->sum('amount');

        return response()->json(['data' => [
            'revenue' => (float) $revenue,
            'costs' => (float) $costs,
            'expenses' => (float) $expenses,
            'gross_profit' => (float) ($revenue - $costs),
            'net_profit' => (float) ($revenue - $costs - $expenses),
        ]]);
    }
}
