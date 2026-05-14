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
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Transfers')]
final class TransferController extends Controller
{
    public function __construct(private readonly TransferService $service) {}

    #[OA\Get(path: '/transfers', summary: 'List transfers', security: [['sanctum' => []]], tags: ['Transfers'],
        parameters: [
            new OA\Parameter(name: 'filter[status]', in: 'query', schema: new OA\Schema(type: 'string', enum: ['pending', 'approved', 'in_transit', 'completed', 'rejected', 'cancelled'])),
            new OA\Parameter(name: 'filter[date_from]', in: 'query', schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'filter[date_to]', in: 'query', schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', default: 20)),
        ],
        responses: [new OA\Response(response: 200, description: 'Transfer list')]
    )]
    public function index(Request $request): ResourceCollection
    {
        $this->authorize('viewAny', Transfer::class);
        $query = Transfer::with('creator')->forUser($request->user());
        if ($status = $request->input('filter.status')) {
            $query->where('status', $status);
        }
        if ($from = $request->input('filter.date_from')) {
            $query->whereDate('date_initiated', '>=', $from);
        }
        if ($to = $request->input('filter.date_to')) {
            $query->whereDate('date_initiated', '<=', $to);
        }

        return TransferResource::collection($query->latest()->paginate($request->integer('per_page', 20)));
    }

    #[OA\Post(path: '/transfers', summary: 'Create a transfer request', security: [['sanctum' => []]], tags: ['Transfers'],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['source_type', 'source_id', 'destination_type', 'destination_id', 'items'],
            properties: [
                new OA\Property(property: 'source_type', type: 'string', enum: ['branch', 'warehouse']),
                new OA\Property(property: 'source_id', type: 'integer'),
                new OA\Property(property: 'destination_type', type: 'string', enum: ['branch', 'warehouse']),
                new OA\Property(property: 'destination_id', type: 'integer'),
                new OA\Property(property: 'note', type: 'string'),
                new OA\Property(property: 'items', type: 'array', items: new OA\Items(
                    required: ['item_id', 'quantity'],
                    properties: [
                        new OA\Property(property: 'item_id', type: 'integer'),
                        new OA\Property(property: 'quantity', type: 'integer', minimum: 1),
                    ]
                )),
            ]
        )),
        responses: [new OA\Response(response: 201, description: 'Transfer created', content: new OA\JsonContent(
            properties: [new OA\Property(property: 'data', ref: '#/components/schemas/Transfer')]
        ))]
    )]
    public function store(StoreTransferRequest $request)
    {
        $data = $request->validated();
        $items = $data['items'];
        unset($data['items']);
        $transfer = $this->service->createTransfer(transferData: $data, items: $items, user: $request->user());

        return (new TransferResource($transfer->load('items.item', 'creator')))->response()->setStatusCode(201);
    }

    #[OA\Get(path: '/transfers/{id}', summary: 'Get a transfer', security: [['sanctum' => []]], tags: ['Transfers'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Transfer detail', content: new OA\JsonContent(
            properties: [new OA\Property(property: 'data', ref: '#/components/schemas/Transfer')]
        ))]
    )]
    public function show(Request $request, Transfer $transfer): TransferResource
    {
        $this->authorize('view', $transfer);

        return new TransferResource($transfer->load('items.item', 'creator', 'approvedBy'));
    }

    #[OA\Delete(path: '/transfers/{id}', summary: 'Cancel and delete a transfer', security: [['sanctum' => []]], tags: ['Transfers'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 204, description: 'Deleted')]
    )]
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

    #[OA\Post(path: '/transfers/{id}/approve', summary: 'Approve a transfer', security: [['sanctum' => []]], tags: ['Transfers'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Transfer approved/completed')]
    )]
    public function approve(Request $request, Transfer $transfer): TransferResource
    {
        $this->authorize('approve', $transfer);

        return $this->workflow($request, $transfer, 'approve');
    }

    #[OA\Post(path: '/transfers/{id}/reject', summary: 'Reject a transfer', security: [['sanctum' => []]], tags: ['Transfers'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Transfer rejected')]
    )]
    public function reject(Request $request, Transfer $transfer): TransferResource
    {
        $this->authorize('approve', $transfer);

        return $this->workflow($request, $transfer, 'reject');
    }

    #[OA\Post(path: '/transfers/{id}/cancel', summary: 'Cancel a transfer', security: [['sanctum' => []]], tags: ['Transfers'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Transfer cancelled')]
    )]
    public function cancel(Request $request, Transfer $transfer): TransferResource
    {
        $this->authorize('delete', $transfer);

        return $this->workflow($request, $transfer, 'cancel');
    }

    #[OA\Post(path: '/transfers/{id}/mark-in-transit', summary: 'Mark transfer as in-transit', security: [['sanctum' => []]], tags: ['Transfers'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Marked in-transit')]
    )]
    public function markInTransit(Request $request, Transfer $transfer): TransferResource
    {
        $this->authorize('update', $transfer);

        return $this->workflow($request, $transfer, 'mark_in_transit');
    }

    #[OA\Post(path: '/transfers/{id}/complete', summary: 'Complete a transfer', security: [['sanctum' => []]], tags: ['Transfers'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Transfer completed')]
    )]
    public function complete(Request $request, Transfer $transfer): TransferResource
    {
        $this->authorize('receive', $transfer);

        return $this->workflow($request, $transfer, 'complete');
    }
}
