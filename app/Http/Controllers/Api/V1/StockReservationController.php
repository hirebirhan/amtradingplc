<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\UserHelper;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\StockReservationResource;
use App\Models\StockReservation;
use App\Services\StockMovementService;
use App\Support\Access\UserAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'StockReservations')]
final class StockReservationController extends Controller
{
    public function __construct(private readonly StockMovementService $stockMovement) {}

    #[OA\Get(path: '/stock-reservations', summary: 'List stock reservations', security: [['sanctum' => []]], tags: ['StockReservations'],
        parameters: [
            new OA\Parameter(name: 'filter[item_id]', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'filter[active_only]', in: 'query', schema: new OA\Schema(type: 'boolean', default: true)),
        ],
        responses: [new OA\Response(response: 200, description: 'Reservation list')]
    )]
    public function index(Request $request): ResourceCollection
    {
        abort_unless($request->user()->can('transfers.view'), 403);
        $query = StockReservation::with('item', 'creator');
        UserAccess::scopeToReservationLocation($query, $request->user());
        if ($request->boolean('filter.active_only', true)) {
            $query->active();
        }
        if ($itemId = $request->integer('filter.item_id') ?: null) {
            $query->where('item_id', $itemId);
        }

        return StockReservationResource::collection($query->orderBy('expires_at')->paginate($request->integer('per_page', 20)));
    }

    #[OA\Get(path: '/stock-reservations/{id}', summary: 'Get a stock reservation', security: [['sanctum' => []]], tags: ['StockReservations'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Reservation detail')]
    )]
    public function show(Request $request, StockReservation $stockReservation): StockReservationResource
    {
        abort_unless($request->user()->can('transfers.view'), 403);
        abort_unless(UserAccess::canAccessReservation($request->user(), $stockReservation), 403);

        return new StockReservationResource($stockReservation->load('item', 'creator'));
    }

    #[OA\Post(path: '/stock-reservations/{id}/release', summary: 'Release a stock reservation', security: [['sanctum' => []]], tags: ['StockReservations'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Released')]
    )]
    public function release(Request $request, StockReservation $stockReservation): JsonResponse
    {
        abort_unless(UserHelper::canManageStockReservations(), 403);
        abort_unless(UserAccess::canAccessReservation($request->user(), $stockReservation), 403);
        $stockReservation->delete();

        return response()->json(['message' => 'Reservation released.']);
    }

    #[OA\Patch(path: '/stock-reservations/{id}/extend', summary: 'Extend a reservation expiry', security: [['sanctum' => []]], tags: ['StockReservations'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['hours'],
            properties: [new OA\Property(property: 'hours', type: 'integer', minimum: 1, maximum: 168)]
        )),
        responses: [new OA\Response(response: 200, description: 'Extended')]
    )]
    public function extend(Request $request, StockReservation $stockReservation): StockReservationResource
    {
        abort_unless(UserHelper::canManageStockReservations(), 403);
        abort_unless(UserAccess::canAccessReservation($request->user(), $stockReservation), 403);
        $request->validate(['hours' => ['required', 'integer', 'min:1', 'max:168']]);
        $stockReservation->update(['expires_at' => $stockReservation->expires_at->addHours($request->integer('hours'))]);

        return new StockReservationResource($stockReservation->fresh());
    }

    #[OA\Post(path: '/stock-reservations/cleanup', summary: 'Delete all expired reservations', security: [['sanctum' => []]], tags: ['StockReservations'],
        responses: [new OA\Response(response: 200, description: 'Cleanup result', content: new OA\JsonContent(
            properties: [new OA\Property(property: 'deleted_count', type: 'integer')]
        ))]
    )]
    public function cleanup(Request $request): JsonResponse
    {
        abort_unless(UserHelper::canManageStockReservations(), 403);
        $count = $this->stockMovement->cleanupExpiredReservations();

        return response()->json(['message' => "Cleaned up {$count} expired reservations.", 'deleted_count' => $count]);
    }
}
