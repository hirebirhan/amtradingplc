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
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Purchases')]
final class PurchaseController extends Controller
{
    public function __construct(private readonly PurchaseService $service) {}

    #[OA\Get(path: '/purchases', summary: 'List purchases', security: [['sanctum' => []]], tags: ['Purchases'],
        parameters: [
            new OA\Parameter(name: 'filter[supplier_id]', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'filter[branch_id]', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'filter[status]', in: 'query', schema: new OA\Schema(type: 'string', enum: ['draft', 'confirmed', 'received', 'cancelled'])),
            new OA\Parameter(name: 'filter[date_from]', in: 'query', schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'filter[date_to]', in: 'query', schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', default: 20)),
        ],
        responses: [new OA\Response(response: 200, description: 'Paginated purchase list')]
    )]
    public function index(Request $request): ResourceCollection
    {
        $this->authorize('viewAny', Purchase::class);
        $query = Purchase::with('supplier', 'branch', 'warehouse')->forUser($request->user());
        if ($supplierId = $request->integer('filter.supplier_id') ?: null) {
            $query->where('supplier_id', $supplierId);
        }
        if ($branchId = $request->integer('filter.branch_id') ?: null) {
            $query->where('branch_id', $branchId);
        }
        if ($status = $request->input('filter.status')) {
            $query->where('status', $status);
        }
        if ($from = $request->input('filter.date_from')) {
            $query->whereDate('purchase_date', '>=', $from);
        }
        if ($to = $request->input('filter.date_to')) {
            $query->whereDate('purchase_date', '<=', $to);
        }
        $sort = $request->input('sort', '-created_at');
        $sortColumn = ltrim($sort, '-');
        $allowedSorts = ['created_at', 'purchase_date', 'total_amount', 'paid_amount', 'due_amount'];
        if (! in_array($sortColumn, $allowedSorts, true)) {
            $sortColumn = 'created_at';
        }
        $query->orderBy($sortColumn, str_starts_with($sort, '-') ? 'desc' : 'asc');

        return PurchaseResource::collection($query->paginate($request->integer('per_page', 20)));
    }

    #[OA\Post(path: '/purchases', summary: 'Create a purchase order', security: [['sanctum' => []]], tags: ['Purchases'],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['branch_id', 'warehouse_id', 'purchase_date', 'payment_method', 'items'],
            properties: [
                new OA\Property(property: 'branch_id', type: 'integer'),
                new OA\Property(property: 'warehouse_id', type: 'integer'),
                new OA\Property(property: 'supplier_id', type: 'integer', nullable: true),
                new OA\Property(property: 'purchase_date', type: 'string', format: 'date'),
                new OA\Property(property: 'payment_method', type: 'string', enum: ['cash', 'bank_transfer', 'telebirr', 'credit_advance', 'full_credit']),
                new OA\Property(property: 'advance_amount', type: 'number'),
                new OA\Property(property: 'note', type: 'string'),
                new OA\Property(property: 'items', type: 'array', items: new OA\Items(
                    required: ['item_id', 'quantity', 'unit_cost'],
                    properties: [
                        new OA\Property(property: 'item_id', type: 'integer'),
                        new OA\Property(property: 'quantity', type: 'integer'),
                        new OA\Property(property: 'unit_cost', type: 'number'),
                    ]
                )),
            ]
        )),
        responses: [new OA\Response(response: 201, description: 'Purchase created', content: new OA\JsonContent(
            properties: [new OA\Property(property: 'data', ref: '#/components/schemas/Purchase')]
        ))]
    )]
    public function store(StorePurchaseRequest $request)
    {
        $data = $request->validated();
        $items = $data['items'];
        unset($data['items']);
        $total = collect($items)->sum(fn ($i) => (float) ($i['subtotal'] ?? ((float) $i['quantity'] * (float) $i['unit_cost'])));
        $purchase = $this->service->createPurchase(actor: $request->user(), formData: $data, items: $items, totalAmount: $total, taxAmount: 0);

        return (new PurchaseResource($purchase->load('supplier', 'branch', 'warehouse', 'items.item')))->response()->setStatusCode(201);
    }

    #[OA\Get(path: '/purchases/{id}', summary: 'Get a purchase', security: [['sanctum' => []]], tags: ['Purchases'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Purchase detail', content: new OA\JsonContent(
            properties: [new OA\Property(property: 'data', ref: '#/components/schemas/Purchase')]
        ))]
    )]
    public function show(Request $request, Purchase $purchase): PurchaseResource
    {
        $this->authorize('view', $purchase);

        return new PurchaseResource($purchase->load('supplier', 'branch', 'warehouse', 'items.item'));
    }

    #[OA\Delete(path: '/purchases/{id}', summary: 'Delete a purchase', security: [['sanctum' => []]], tags: ['Purchases'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 204, description: 'Deleted')]
    )]
    public function destroy(Request $request, Purchase $purchase): JsonResponse
    {
        $this->authorize('delete', $purchase);
        $purchase->update(['deleted_by' => $request->user()->id]);
        $purchase->delete();

        return response()->json(null, 204);
    }
}
