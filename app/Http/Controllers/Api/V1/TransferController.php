<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Transfers\StoreTransferRequest;
use App\Http\Resources\Api\V1\TransferResource;
use App\Models\Transfer;
use App\Services\TransferService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

final class TransferController extends Controller
{
    public function __construct(private readonly TransferService $service) {}

    public function index(Request $request): ResourceCollection
    {
        $this->authorize('viewAny', Transfer::class);

        $query = Transfer::with('creator')
            ->forUser($request->user());

        if ($status = $request->input('filter.status')) {
            $query->where('status', $status);
        }
        if ($from = $request->input('filter.date_from')) {
            $query->whereDate('date_initiated', '>=', $from);
        }
        if ($to = $request->input('filter.date_to')) {
            $query->whereDate('date_initiated', '<=', $to);
        }

        return TransferResource::collection(
            $query->latest()->paginate($request->integer('per_page', 20))
        );
    }

    public function store(StoreTransferRequest $request)
    {
        $data  = $request->validated();
        $items = $data['items'];
        unset($data['items']);

        $transfer = $this->service->createTransfer(
            transferData: $data,
            items: $items,
            user: $request->user(),
        );

        return (new TransferResource($transfer->load('items.item', 'creator')))->response()->setStatusCode(201);
    }

    public function show(Request $request, Transfer $transfer): TransferResource
    {
        $this->authorize('view', $transfer);
        return new TransferResource($transfer->load('items.item', 'creator', 'approvedBy'));
    }

    public function destroy(Request $request, Transfer $transfer): JsonResponse
    {
        $this->authorize('delete', $transfer);

        $this->service->processTransferWorkflow($transfer, $request->user(), 'cancel');
        $transfer->update(['deleted_by' => $request->user()->id]);
        $transfer->delete();

        return response()->json(null, 204);
    }

    private function workflow(Request $request, Transfer $transfer, string $action): TransferResource
    {
        $this->service->processTransferWorkflow($transfer, $request->user(), $action);
        return new TransferResource($transfer->fresh()->load('items.item', 'creator', 'approvedBy'));
    }

    public function approve(Request $request, Transfer $transfer): TransferResource
    {
        abort_unless($request->user()->can('transfers.approve'), 403);
        return $this->workflow($request, $transfer, 'approve');
    }

    public function reject(Request $request, Transfer $transfer): TransferResource
    {
        abort_unless($request->user()->can('transfers.approve'), 403);
        return $this->workflow($request, $transfer, 'reject');
    }

    public function cancel(Request $request, Transfer $transfer): TransferResource
    {
        $this->authorize('delete', $transfer);
        return $this->workflow($request, $transfer, 'cancel');
    }

    public function markInTransit(Request $request, Transfer $transfer): TransferResource
    {
        abort_unless($request->user()->can('transfers.edit'), 403);
        return $this->workflow($request, $transfer, 'mark_in_transit');
    }

    public function complete(Request $request, Transfer $transfer): TransferResource
    {
        abort_unless($request->user()->can('transfers.receive'), 403);
        return $this->workflow($request, $transfer, 'complete');
    }
}
