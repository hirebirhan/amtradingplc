<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Purchases\StorePurchaseRequest;
use App\Http\Resources\Api\V1\PurchaseResource;
use App\Models\Purchase;
use App\Services\PurchaseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

final class PurchaseController extends Controller
{
    public function __construct(private readonly PurchaseService $service) {}

    public function index(Request $request): ResourceCollection
    {
        $this->authorize('viewAny', Purchase::class);

        $query = Purchase::with('supplier', 'branch', 'warehouse')
            ->forUser($request->user());

        if ($supplierId = $request->integer('filter.supplier_id') ?: null) {
            $query->where('supplier_id', $supplierId);
        }
        if ($branchId = $request->integer('filter.branch_id') ?: null) {
            $query->where('branch_id', $branchId);
        }
        if ($status = $request->input('filter.status')) {
            $query->where('status', $status);
        }
        if ($paymentStatus = $request->input('filter.payment_status')) {
            $query->where('payment_status', $paymentStatus);
        }
        if ($from = $request->input('filter.date_from')) {
            $query->whereDate('purchase_date', '>=', $from);
        }
        if ($to = $request->input('filter.date_to')) {
            $query->whereDate('purchase_date', '<=', $to);
        }

        $sort = $request->input('sort', '-created_at');
        $dir  = str_starts_with($sort, '-') ? 'desc' : 'asc';
        $col  = ltrim($sort, '-');
        $query->orderBy($col, $dir);

        return PurchaseResource::collection(
            $query->paginate($request->integer('per_page', 20))
        );
    }

    public function store(StorePurchaseRequest $request)
    {
        $data  = $request->validated();
        $items = $data['items'];
        unset($data['items']);

        $total = collect($items)->sum(fn ($i) => (float) ($i['subtotal'] ?? ((float) $i['quantity'] * (float) $i['unit_cost'])));
        $tax   = 0;

        $purchase = $this->service->createPurchase(
            actor: $request->user(),
            formData: $data,
            items: $items,
            totalAmount: $total,
            taxAmount: $tax,
        );

        return (new PurchaseResource($purchase->load('supplier', 'branch', 'warehouse', 'items.item')))->response()->setStatusCode(201);
    }

    public function show(Request $request, Purchase $purchase): PurchaseResource
    {
        $this->authorize('view', $purchase);
        return new PurchaseResource($purchase->load('supplier', 'branch', 'warehouse', 'items.item'));
    }

    public function destroy(Request $request, Purchase $purchase): JsonResponse
    {
        $this->authorize('delete', $purchase);
        $purchase->update(['deleted_by' => $request->user()->id]);
        $purchase->delete();
        return response()->json(null, 204);
    }
}
