<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Credits\StoreCreditPaymentRequest;
use App\Http\Resources\Api\V1\CreditPaymentResource;
use App\Http\Resources\Api\V1\CreditResource;
use App\Models\Credit;
use App\Services\CreditPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Credits')]
final class CreditController extends Controller
{
    public function __construct(private readonly CreditPaymentService $service) {}

    #[OA\Get(path: '/credits', summary: 'List credits', security: [['sanctum' => []]], tags: ['Credits'],
        parameters: [
            new OA\Parameter(name: 'filter[credit_type]', in: 'query', schema: new OA\Schema(type: 'string', enum: ['receivable', 'payable'])),
            new OA\Parameter(name: 'filter[status]', in: 'query', schema: new OA\Schema(type: 'string', enum: ['open', 'partial', 'paid', 'closed'])),
            new OA\Parameter(name: 'filter[branch_id]', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', default: 20)),
        ],
        responses: [new OA\Response(response: 200, description: 'Credit list')]
    )]
    public function index(Request $request): ResourceCollection
    {
        $this->authorize('viewAny', Credit::class);
        $query = Credit::with('customer', 'supplier');
        if ($type = $request->input('filter.credit_type')) { $query->where('credit_type', $type); }
        if ($status = $request->input('filter.status')) { $query->where('status', $status); }
        if ($branchId = $request->integer('filter.branch_id') ?: null) { $query->where('branch_id', $branchId); }
        return CreditResource::collection($query->latest()->paginate($request->integer('per_page', 20)));
    }

    #[OA\Get(path: '/credits/{id}', summary: 'Get a credit', security: [['sanctum' => []]], tags: ['Credits'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Credit detail', content: new OA\JsonContent(
            properties: [new OA\Property(property: 'data', ref: '#/components/schemas/Credit')]
        ))]
    )]
    public function show(Request $request, Credit $credit): CreditResource
    {
        $this->authorize('view', $credit);
        return new CreditResource($credit->load('customer', 'supplier', 'payments'));
    }

    #[OA\Get(path: '/credits/{id}/payments', summary: 'List payments for a credit', security: [['sanctum' => []]], tags: ['Credits'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Payment list')]
    )]
    public function payments(Request $request, Credit $credit): ResourceCollection
    {
        $this->authorize('view', $credit);
        return CreditPaymentResource::collection($credit->payments()->latest()->paginate(20));
    }

    #[OA\Post(path: '/credits/{id}/payments', summary: 'Add a payment to a credit', security: [['sanctum' => []]], tags: ['Credits'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['amount', 'payment_method'],
            properties: [
                new OA\Property(property: 'amount', type: 'number', minimum: 0.01),
                new OA\Property(property: 'payment_method', type: 'string', enum: ['cash', 'bank_transfer', 'telebirr', 'credit_card', 'check', 'other']),
                new OA\Property(property: 'payment_date', type: 'string', format: 'date'),
                new OA\Property(property: 'notes', type: 'string'),
                new OA\Property(property: 'reference_no', type: 'string'),
            ]
        )),
        responses: [new OA\Response(response: 201, description: 'Payment recorded')]
    )]
    public function addPayment(StoreCreditPaymentRequest $request, Credit $credit): CreditPaymentResource
    {
        $this->authorize('update', $credit);
        $request->validate(['amount' => ['max:' . $credit->balance]]);
        $payment = $credit->addPayment(
            amount: (float) $request->input('amount'),
            paymentMethod: $request->input('payment_method'),
            reference: $request->input('reference_no'),
            notes: $request->input('notes'),
            paymentDate: $request->input('payment_date'),
        );
        return (new CreditPaymentResource($payment))->response()->setStatusCode(201);
    }

    #[OA\Get(path: '/credits/{id}/closing-offer', summary: 'Get closing offer for a credit', security: [['sanctum' => []]], tags: ['Credits'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Closing offer details')]
    )]
    public function closingOffer(Request $request, Credit $credit): JsonResponse
    {
        $this->authorize('view', $credit);
        abort_unless($this->service->isEligibleForClosingOffer($credit), 403, 'Credit is not eligible for a closing offer.');
        $offer = $this->service->calculateClosingOffer($credit);
        return response()->json(['data' => $offer]);
    }

    #[OA\Post(path: '/credits/{id}/closing-offer/calculate', summary: 'Calculate closing offer with negotiated prices', security: [['sanctum' => []]], tags: ['Credits'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['negotiated_prices'],
            properties: [new OA\Property(property: 'negotiated_prices', type: 'array', items: new OA\Items(
                required: ['item_id', 'price'],
                properties: [new OA\Property(property: 'item_id', type: 'integer'), new OA\Property(property: 'price', type: 'number')]
            ))]
        )),
        responses: [new OA\Response(response: 200, description: 'Profit/loss calculation')]
    )]
    public function calculateClosingOffer(Request $request, Credit $credit): JsonResponse
    {
        $this->authorize('update', $credit);
        abort_unless($this->service->isEligibleForClosingOffer($credit), 403, 'Credit is not eligible for a closing offer.');
        $request->validate(['negotiated_prices' => ['required', 'array'], 'negotiated_prices.*.item_id' => ['required', 'exists:items,id'], 'negotiated_prices.*.price' => ['required', 'numeric', 'min:0']]);
        $result = $this->service->calculateProfitLossFromNegotiatedPrices($credit, $request->input('negotiated_prices'));
        return response()->json(['data' => $result]);
    }

    #[OA\Post(path: '/credits/{id}/closing-offer/accept', summary: 'Accept closing offer', security: [['sanctum' => []]], tags: ['Credits'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['negotiated_prices'],
            properties: [new OA\Property(property: 'negotiated_prices', type: 'array', items: new OA\Items(
                required: ['item_id', 'price'],
                properties: [new OA\Property(property: 'item_id', type: 'integer'), new OA\Property(property: 'price', type: 'number')]
            ))]
        )),
        responses: [new OA\Response(response: 200, description: 'Closing offer accepted')]
    )]
    public function acceptClosingOffer(Request $request, Credit $credit): JsonResponse
    {
        $this->authorize('update', $credit);
        abort_unless($this->service->isEligibleForClosingOffer($credit), 403, 'Credit is not eligible for a closing offer.');
        $request->validate(['negotiated_prices' => ['required', 'array'], 'negotiated_prices.*.item_id' => ['required', 'exists:items,id'], 'negotiated_prices.*.price' => ['required', 'numeric', 'min:0']]);
        $result = $this->service->processEarlyClosureWithNegotiatedPrices($credit, $request->input('negotiated_prices'), forceClose: false);
        return response()->json(['data' => $result]);
    }
}
