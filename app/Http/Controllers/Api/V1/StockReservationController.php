<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\UserHelper;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\StockReservationResource;
use App\Models\StockReservation;
use App\Services\StockMovementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

final class StockReservationController extends Controller
{
    public function __construct(private readonly StockMovementService $stockMovement) {}

    public function index(Request $request): ResourceCollection
    {
        abort_unless($request->user()->can('transfers.view'), 403);

        $query = StockReservation::with('item', 'creator');

        if (! UserHelper::canManageStockReservations()) {
            $warehouseIds = UserHelper::getAccessibleWarehouseIds();
            $query->where(function ($q) use ($warehouseIds) {
                $q->where('location_type', 'warehouse')->whereIn('location_id', $warehouseIds);
            });
        }

        if ($request->boolean('filter.active_only', true)) {
            $query->active();
        }

        if ($itemId = $request->integer('filter.item_id') ?: null) {
            $query->where('item_id', $itemId);
        }

        return StockReservationResource::collection(
            $query->orderBy('expires_at')->paginate($request->integer('per_page', 20))
        );
    }

    public function show(Request $request, StockReservation $stockReservation): StockReservationResource
    {
        abort_unless($request->user()->can('transfers.view'), 403);
        return new StockReservationResource($stockReservation->load('item', 'creator'));
    }

    public function release(Request $request, StockReservation $stockReservation): JsonResponse
    {
        abort_unless(UserHelper::canManageStockReservations(), 403);
        $stockReservation->delete();
        return response()->json(['message' => 'Reservation released.']);
    }

    public function extend(Request $request, StockReservation $stockReservation): StockReservationResource
    {
        abort_unless(UserHelper::canManageStockReservations(), 403);
        $request->validate(['hours' => ['required', 'integer', 'min:1', 'max:168']]);

        $stockReservation->update([
            'expires_at' => $stockReservation->expires_at->addHours($request->integer('hours')),
        ]);

        return new StockReservationResource($stockReservation->fresh());
    }

    public function cleanup(Request $request): JsonResponse
    {
        abort_unless(UserHelper::canManageStockReservations(), 403);
        $count = $this->stockMovement->cleanupExpiredReservations();
        return response()->json(['message' => "Cleaned up {$count} expired reservations.", 'deleted_count' => $count]);
    }
}
