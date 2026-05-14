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
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Sales')]
final class SaleController extends Controller
{
    public function __construct(private readonly SaleService $service) {}

    #[OA\Get(path: '/sales', summary: 'List sales', security: [['sanctum' => []]], tags: ['Sales'],
        parameters: [
            new OA\Parameter(name: 'filter[customer_id]', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'filter[branch_id]', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'filter[payment_status]', in: 'query', schema: new OA\Schema(type: 'string', enum: ['paid', 'partial', 'credit', 'unpaid'])),
            new OA\Parameter(name: 'filter[date_from]', in: 'query', schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'filter[date_to]', in: 'query', schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', default: 20)),
        ],
        responses: [new OA\Response(response: 200, description: 'Paginated sale list')]
    )]
    public function index(Request $request): ResourceCollection
    {
        $this->authorize('viewAny', Sale::class);
        $query = Sale::with('customer', 'branch', 'warehouse')->forUser($request->user());
        if ($customerId = $request->integer('filter.customer_id') ?: null) { $query->where('customer_id', $customerId); }
        if ($branchId = $request->integer('filter.branch_id') ?: null) { $query->where('branch_id', $branchId); }
        if ($status = $request->input('filter.payment_status')) { $query->where('payment_status', $status); }
        if ($from = $request->input('filter.date_from')) { $query->whereDate('sale_date', '>=', $from); }
        if ($to = $request->input('filter.date_to')) { $query->whereDate('sale_date', '<=', $to); }
        $sort = $request->input('sort', '-created_at');
        $query->orderBy(ltrim($sort, '-'), str_starts_with($sort, '-') ? 'desc' : 'asc');
        return SaleResource::collection($query->paginate($request->integer('per_page', 20)));
    }

    #[OA\Post(path: '/sales', summary: 'Create a sale', security: [['sanctum' => []]], tags: ['Sales'],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['branch_id', 'warehouse_id', 'sale_date', 'payment_method', 'items'],
            properties: [
                new OA\Property(property: 'branch_id', type: 'integer'),
                new OA\Property(property: 'warehouse_id', type: 'integer'),
                new OA\Property(property: 'customer_id', type: 'integer', nullable: true),
                new OA\Property(property: 'sale_date', type: 'string', format: 'date'),
                new OA\Property(property: 'payment_method', type: 'string', enum: ['cash', 'bank_transfer', 'telebirr', 'credit_card', 'check', 'full_credit', 'credit_advance']),
                new OA\Property(property: 'advance_amount', type: 'number'),
                new OA\Property(property: 'note', type: 'string'),
                new OA\Property(property: 'items', type: 'array', items: new OA\Items(
                    required: ['item_id', 'quantity', 'unit_price'],
                    properties: [
                        new OA\Property(property: 'item_id', type: 'integer'),
                        new OA\Property(property: 'quantity', type: 'integer'),
                        new OA\Property(property: 'unit_price', type: 'number'),
                    ]
                )),
            ]
        )),
        responses: [new OA\Response(response: 201, description: 'Sale created', content: new OA\JsonContent(
            properties: [new OA\Property(property: 'data', ref: '#/components/schemas/Sale')]
        ))]
    )]
    public function store(StoreSaleRequest $request)
    {
        $sale = $this->service->createSale(actor: $request->user(), data: $request->validated());
        return (new SaleResource($sale->load('customer', 'branch', 'warehouse', 'items.item', 'credit')))->response()->setStatusCode(201);
    }

    #[OA\Get(path: '/sales/{id}', summary: 'Get a sale', security: [['sanctum' => []]], tags: ['Sales'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Sale detail', content: new OA\JsonContent(
            properties: [new OA\Property(property: 'data', ref: '#/components/schemas/Sale')]
        ))]
    )]
    public function show(Request $request, Sale $sale): SaleResource
    {
        $this->authorize('view', $sale);
        return new SaleResource($sale->load('customer', 'branch', 'warehouse', 'items.item', 'credit'));
    }

    #[OA\Delete(path: '/sales/{id}', summary: 'Delete a sale', security: [['sanctum' => []]], tags: ['Sales'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 204, description: 'Deleted')]
    )]
    public function destroy(Request $request, Sale $sale): JsonResponse
    {
        $this->authorize('delete', $sale);
        $sale->update(['deleted_by' => $request->user()->id]);
        $sale->delete();
        return response()->json(null, 204);
    }
}
