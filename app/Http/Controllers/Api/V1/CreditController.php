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

final class CreditController extends Controller
{
    public function __construct(private readonly CreditPaymentService $service) {}

    public function index(Request $request): ResourceCollection
    {
        $this->authorize('viewAny', Credit::class);

        $query = Credit::with('customer', 'supplier');

        if ($type = $request->input('filter.credit_type')) {
            $query->where('credit_type', $type);
        }
        if ($status = $request->input('filter.status')) {
            $query->where('status', $status);
        }
        if ($branchId = $request->integer('filter.branch_id') ?: null) {
            $query->where('branch_id', $branchId);
        }

        return CreditResource::collection(
            $query->latest()->paginate($request->integer('per_page', 20))
        );
    }

    public function show(Request $request, Credit $credit): CreditResource
    {
        $this->authorize('view', $credit);
        return new CreditResource($credit->load('customer', 'supplier', 'payments'));
    }

    // ── Payments sub-resource ────────────────────────────────────────────────

    public function payments(Request $request, Credit $credit): ResourceCollection
    {
        $this->authorize('view', $credit);
        return CreditPaymentResource::collection($credit->payments()->latest()->paginate(20));
    }

    public function addPayment(StoreCreditPaymentRequest $request, Credit $credit): CreditPaymentResource
    {
        $this->authorize('update', $credit);

        $request->validate([
            'amount' => ['max:' . $credit->balance],
        ]);

        $payment = $credit->addPayment(
            amount: (float) $request->input('amount'),
            paymentMethod: $request->input('payment_method'),
            reference: $request->input('reference_no'),
            notes: $request->input('notes'),
            paymentDate: $request->input('payment_date'),
        );

        return (new CreditPaymentResource($payment))->response()->setStatusCode(201);
    }

    // ── Closing offer ────────────────────────────────────────────────────────

    public function closingOffer(Request $request, Credit $credit): JsonResponse
    {
        $this->authorize('view', $credit);
        abort_unless($this->service->isEligibleForClosingOffer($credit), 403, 'Credit is not eligible for a closing offer.');

        $offer = $this->service->calculateClosingOffer($credit);
        return response()->json(['data' => $offer]);
    }

    public function calculateClosingOffer(Request $request, Credit $credit): JsonResponse
    {
        $this->authorize('update', $credit);
        abort_unless($this->service->isEligibleForClosingOffer($credit), 403, 'Credit is not eligible for a closing offer.');

        $request->validate([
            'negotiated_prices'           => ['required', 'array'],
            'negotiated_prices.*.item_id' => ['required', 'exists:items,id'],
            'negotiated_prices.*.price'   => ['required', 'numeric', 'min:0'],
        ]);

        $result = $this->service->calculateProfitLossFromNegotiatedPrices($credit, $request->input('negotiated_prices'));
        return response()->json(['data' => $result]);
    }

    public function acceptClosingOffer(Request $request, Credit $credit): JsonResponse
    {
        $this->authorize('update', $credit);
        abort_unless($this->service->isEligibleForClosingOffer($credit), 403, 'Credit is not eligible for a closing offer.');

        $request->validate([
            'negotiated_prices'           => ['required', 'array'],
            'negotiated_prices.*.item_id' => ['required', 'exists:items,id'],
            'negotiated_prices.*.price'   => ['required', 'numeric', 'min:0'],
        ]);

        $result = $this->service->processEarlyClosureWithNegotiatedPrices(
            $credit,
            $request->input('negotiated_prices'),
            forceClose: false
        );

        return response()->json(['data' => $result]);
    }
}
