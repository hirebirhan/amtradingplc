<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Sales\StoreSaleRequest;
use App\Http\Resources\Api\V1\SaleResource;
use App\Models\Sale;
use App\Services\SaleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

final class SaleController extends Controller
{
    public function __construct(private readonly SaleService $service) {}

    public function index(Request $request): ResourceCollection
    {
        $this->authorize('viewAny', Sale::class);

        $query = Sale::with('customer', 'branch', 'warehouse')
            ->forUser($request->user());

        if ($customerId = $request->integer('filter.customer_id') ?: null) {
            $query->where('customer_id', $customerId);
        }
        if ($branchId = $request->integer('filter.branch_id') ?: null) {
            $query->where('branch_id', $branchId);
        }
        if ($warehouseId = $request->integer('filter.warehouse_id') ?: null) {
            $query->where('warehouse_id', $warehouseId);
        }
        if ($status = $request->input('filter.status')) {
            $query->where('status', $status);
        }
        if ($paymentStatus = $request->input('filter.payment_status')) {
            $query->where('payment_status', $paymentStatus);
        }
        if ($from = $request->input('filter.date_from')) {
            $query->whereDate('sale_date', '>=', $from);
        }
        if ($to = $request->input('filter.date_to')) {
            $query->whereDate('sale_date', '<=', $to);
        }

        $sort = $request->input('sort', '-created_at');
        $dir  = str_starts_with($sort, '-') ? 'desc' : 'asc';
        $query->orderBy(ltrim($sort, '-'), $dir);

        return SaleResource::collection(
            $query->paginate($request->integer('per_page', 20))
        );
    }

    public function store(StoreSaleRequest $request)
    {
        $sale = $this->service->createSale(
            actor: $request->user(),
            data: $request->validated(),
        );

        return (new SaleResource($sale->load('customer', 'branch', 'warehouse', 'items.item', 'credit')))->response()->setStatusCode(201);
    }

    public function show(Request $request, Sale $sale): SaleResource
    {
        $this->authorize('view', $sale);
        return new SaleResource($sale->load('customer', 'branch', 'warehouse', 'items.item', 'credit'));
    }

    public function destroy(Request $request, Sale $sale): JsonResponse
    {
        $this->authorize('delete', $sale);
        $sale->update(['deleted_by' => $request->user()->id]);
        $sale->delete();
        return response()->json(null, 204);
    }
}
