<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StockReservation;
use App\Services\StockMovementService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use UserHelper;

class StockReservationController extends Controller
{
    private StockMovementService $stockMovementService;

    public function __construct()
    {
        $this->middleware('auth');
        $this->stockMovementService = new StockMovementService();
    }

    /**
     * Display stock reservations dashboard
     */
    public function index()
    {
        // Start building the query
        $activeQuery = StockReservation::active()->with(['item', 'creator']);
        
        // Filter by warehouse if user doesn't have global access
        if (!UserHelper::canManageStockReservations()) {
            $warehouseIds = UserHelper::getAccessibleWarehouseIds();
            $activeQuery->whereIn('warehouse_id', $warehouseIds);
        }
        
        $activeReservations = $activeQuery->orderBy('expires_at', 'asc')
            ->paginate(20);

        // Get expired reservations (last 50)
        $expiredQuery = StockReservation::expired()->with(['item', 'creator']);
        
        // Filter by warehouse if user doesn't have global access
        if (!UserHelper::canManageStockReservations()) {
            $warehouseIds = UserHelper::getAccessibleWarehouseIds();
            $expiredQuery->whereIn('warehouse_id', $warehouseIds);
        }
        
        $expiredReservations = $expiredQuery->orderBy('expires_at', 'desc')
            ->limit(50)
            ->get();

        // Calculate statistics with access control
        $statsQuery = StockReservation::query();
        
        if (!UserHelper::canManageStockReservations()) {
            $warehouseIds = UserHelper::getAccessibleWarehouseIds();
            $statsQuery->whereIn('warehouse_id', $warehouseIds);
        }
        
        $activeQuery = (clone $statsQuery)->active();
        $expiredQuery = (clone $statsQuery)->expired();
        
        $stats = [
            'active_reservations' => $activeQuery->count(),
            'expired_reservations' => $expiredQuery->count(),
            'total_reserved_quantity' => $activeQuery->sum('quantity'),
            'unique_items' => $activeQuery->distinct('item_id')->count(),
        ];

        return view('admin.stock-reservations', compact(
            'activeReservations',
            'expiredReservations', 
            'stats'
        ));
    }

    /**
     * Cleanup expired reservations
     */
    public function cleanup(): JsonResponse
    {
        try {
            $deletedCount = $this->stockMovementService->cleanupExpiredReservations();
            
            return response()->json([
                'success' => true,
                'message' => "Successfully cleaned up {$deletedCount} expired reservations.",
                'deleted_count' => $deletedCount,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error cleaning up reservations: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Release a specific reservation
     */
    public function release(StockReservation $reservation): JsonResponse
    {
        try {
            // Check permissions
            if (!auth()->user()->canManageStockReservations()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized action.',
                ], 403);
            }

            $itemName = $reservation->item->name;
            $quantity = $reservation->quantity;
            
            $reservation->delete();
            
            return response()->json([
                'success' => true,
                'message' => "Released reservation for {$quantity} units of {$itemName}.",
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error releasing reservation: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get reservation details via API
     */
    public function show(StockReservation $reservation): JsonResponse
    {
        $reservation->load(['item', 'creator']);
        
        return response()->json([
            'success' => true,
            'reservation' => [
                'id' => $reservation->id,
                'item' => [
                    'id' => $reservation->item->id,
                    'name' => $reservation->item->name,
                    'sku' => $reservation->item->sku,
                ],
                'location_type' => $reservation->location_type,
                'location_id' => $reservation->location_id,
                'location_name' => $reservation->location_name,
                'quantity' => $reservation->quantity,
                'reference_type' => $reservation->reference_type,
                'reference_id' => $reservation->reference_id,
                'expires_at' => $reservation->expires_at->toISOString(),
                'is_expired' => $reservation->isExpired(),
                'created_by' => [
                    'id' => $reservation->creator->id,
                    'name' => $reservation->creator->name,
                ],
                'created_at' => $reservation->created_at->toISOString(),
            ],
        ]);
    }

    /**
     * Get reservations for a specific item
     */
    public function forItem(Request $request): JsonResponse
    {
        $request->validate([
            'item_id' => 'required|exists:items,id',
            'location_type' => 'nullable|in:warehouse,branch',
            'location_id' => 'nullable|integer',
        ]);

        $query = StockReservation::where('item_id', $request->item_id)
            ->active()
            ->with(['creator']);

        if ($request->location_type && $request->location_id) {
            $query->forLocation($request->location_type, $request->location_id);
        }

        $reservations = $query->orderBy('expires_at', 'asc')->get();

        return response()->json([
            'success' => true,
            'reservations' => $reservations->map(function ($reservation) {
                return [
                    'id' => $reservation->id,
                    'quantity' => $reservation->quantity,
                    'reference_type' => $reservation->reference_type,
                    'reference_id' => $reservation->reference_id,
                    'expires_at' => $reservation->expires_at->toISOString(),
                    'created_by' => $reservation->creator->name,
                    'location_name' => $reservation->location_name,
                ];
            }),
            'total_reserved' => $reservations->sum('quantity'),
        ]);
    }

    /**
     * Extend reservation expiry (admin only)
     */
    public function extend(Request $request, StockReservation $reservation): JsonResponse
    {
        try {
            // Check permissions
            if (!auth()->user()->canManageStockReservations()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized action.',
                ], 403);
            }

            $request->validate([
                'hours' => 'required|integer|min:1|max:168', // Max 1 week
            ]);

            $reservation->expires_at = $reservation->expires_at->addHours($request->hours);
            $reservation->save();

            return response()->json([
                'success' => true,
                'message' => "Reservation extended by {$request->hours} hours.",
                'new_expiry' => $reservation->expires_at->toISOString(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error extending reservation: ' . $e->getMessage(),
            ], 500);
        }
    }
} 