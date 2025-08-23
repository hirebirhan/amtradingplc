<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Stock;
use App\Models\StockReservation;
use App\Models\Item;
use App\Models\Warehouse;
use App\Models\Branch;
use App\Services\StockMovementService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use UserHelper;

class StockReportController extends Controller
{
    private StockMovementService $stockMovementService;

    public function __construct()
    {
        $this->middleware('auth');
        $this->stockMovementService = new StockMovementService();
    }

    /**
     * Display the stock reports page
     */
    public function index()
    {
        return view('admin.stock-reports');
    }

    /**
     * Generate stock report via API
     */
    public function generateReport(Request $request): JsonResponse
    {
        try {
            // Check if user has access to the requested location
            $locationType = $request->input('location_type');
            $locationId = $request->input('location_id');
            
            if ($locationType === 'warehouse' && $locationId) {
                if (!UserHelper::hasAccessToWarehouse($locationId)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'You do not have permission to access this warehouse.'
                    ], 403);
                }
            } elseif ($locationType === 'branch' && $locationId) {
                if (!UserHelper::hasAccessToBranch($locationId)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'You do not have permission to access this branch.'
                    ], 403);
                }
            } elseif (!UserHelper::isSuperAdmin()) {
                // Only super admin can view all locations
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to view all locations.'
                ], 403);
            }
            
            $validated = $request->validate([
                'location_type' => 'nullable|in:warehouse,branch',
                'location_id' => 'nullable|integer',
                'report_type' => 'nullable|in:all,low_stock,zero_stock,with_reservations',
                'with_reservations' => 'nullable|boolean',
            ]);

            $locationType = $validated['location_type'] ?? null;
            $locationId = $validated['location_id'] ?? null;
            $reportType = $validated['report_type'] ?? 'all';
            $withReservations = $validated['with_reservations'] ?? false;

            // Build query based on filters
            $query = $this->buildStockQuery($locationType, $locationId, $reportType);
            $stocks = $query->get();

            if ($stocks->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'data' => [],
                    'message' => 'No stock records found matching the criteria.',
                ]);
            }

            // Prepare report data
            $reportData = $this->prepareReportData($stocks, $withReservations);
            
            // Calculate summary statistics
            $summary = $this->calculateSummary($reportData);

            // Get reservation details if requested
            $reservationDetails = [];
            if ($withReservations) {
                $reservationDetails = $this->getReservationDetails($reportData);
            }

            return response()->json([
                'success' => true,
                'data' => $reportData,
                'summary' => $summary,
                'with_reservations' => $withReservations,
                'reservation_details' => $reservationDetails,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error generating report: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Export stock report
     */
    public function exportReport(Request $request): Response
    {
        try {
            $validated = $request->validate([
                'location_type' => 'nullable|in:warehouse,branch',
                'location_id' => 'nullable|integer',
                'report_type' => 'nullable|in:all,low_stock,zero_stock,with_reservations',
                'with_reservations' => 'nullable|boolean',
                'format' => 'required|in:csv,json',
            ]);

            $locationType = $validated['location_type'] ?? null;
            $locationId = $validated['location_id'] ?? null;
            $reportType = $validated['report_type'] ?? 'all';
            $withReservations = $validated['with_reservations'] ?? false;
            $format = $validated['format'];

            // Build query and get data
            $query = $this->buildStockQuery($locationType, $locationId, $reportType);
            $stocks = $query->get();
            $reportData = $this->prepareReportData($stocks, $withReservations);

            if ($format === 'csv') {
                return $this->exportCsv($reportData, $withReservations);
            } else {
                return $this->exportJson($reportData, $withReservations);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error exporting report: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Build stock query based on filters
     */
    private function buildStockQuery(?string $locationType, ?int $locationId, string $reportType)
    {
        $query = Stock::with(['item', 'warehouse']);

        // Filter by location
        if ($locationType && $locationId) {
            if ($locationType === 'warehouse') {
                $query->where('warehouse_id', $locationId);
            } elseif ($locationType === 'branch') {
                $branch = Branch::with('warehouses')->find($locationId);
                if ($branch) {
                    $query->whereIn('warehouse_id', $branch->warehouses->pluck('id'));
                }
            }
        }

        // Filter by report type
        switch ($reportType) {
            case 'low_stock':
                $query->whereHas('item', function ($q) {
                    $q->whereRaw('reorder_level > 0');
                })->whereRaw('quantity < (SELECT reorder_level FROM items WHERE items.id = stocks.item_id)');
                break;
            
            case 'zero_stock':
                $query->where('quantity', 0);
                break;
            
            case 'with_reservations':
                $query->whereHas('item', function ($q) {
                    $q->whereExists(function ($subQuery) {
                        $subQuery->select(DB::raw(1))
                            ->from('stock_reservations')
                            ->whereColumn('stock_reservations.item_id', 'items.id')
                            ->where('expires_at', '>', now());
                    });
                });
                break;
            
            case 'all':
            default:
                // No additional filtering
                break;
        }

        return $query->orderBy('quantity', 'asc');
    }

    /**
     * Prepare report data
     */
    private function prepareReportData($stocks, bool $withReservations): array
    {
        $reportData = [];

        foreach ($stocks as $stock) {
            $reservedQty = 0;
            $reservationDetails = [];

            if ($withReservations) {
                $reservedQty = $this->stockMovementService->getReservedStock(
                    $stock->item_id,
                    'warehouse',
                    $stock->warehouse_id
                );

                if ($reservedQty > 0) {
                    $reservations = StockReservation::forItem($stock->item_id)
                        ->forLocation('warehouse', $stock->warehouse_id)
                        ->active()
                        ->with(['creator'])
                        ->get();

                    $reservationDetails = $reservations->map(function ($reservation) {
                        return [
                            'reference' => "{$reservation->reference_type}:{$reservation->reference_id}",
                            'quantity' => $reservation->quantity,
                            'expires_at' => $reservation->expires_at->format('Y-m-d H:i'),
                            'created_by' => $reservation->creator->name ?? 'Unknown',
                        ];
                    })->toArray();
                }
            }

            $availableQty = $stock->quantity - $reservedQty;
            $isLowStock = $stock->item->reorder_level > 0 && $stock->quantity < $stock->item->reorder_level;

            $reportData[] = [
                'item_name' => $stock->item->name,
                'item_sku' => $stock->item->sku,
                'warehouse' => $stock->warehouse->name,
                'total_quantity' => $stock->quantity,
                'reserved_quantity' => $reservedQty,
                'available_quantity' => $availableQty,
                'reorder_level' => $stock->item->reorder_level ?? 0,
                'is_low_stock' => $isLowStock,
                'unit_cost' => $stock->item->cost_price ?? 0,
                'total_value' => ($stock->item->cost_price ?? 0) * $stock->quantity,
                'reservations' => $reservationDetails,
            ];
        }

        return $reportData;
    }

    /**
     * Calculate summary statistics
     */
    private function calculateSummary(array $reportData): array
    {
        $totalItems = count($reportData);
        $lowStockItems = array_filter($reportData, fn($item) => $item['is_low_stock']);
        $totalValue = array_sum(array_column($reportData, 'total_value'));
        $totalReserved = array_sum(array_column($reportData, 'reserved_quantity'));

        return [
            'total_items' => $totalItems,
            'low_stock_items' => count($lowStockItems),
            'total_value' => $totalValue,
            'total_reserved' => $totalReserved,
        ];
    }

    /**
     * Get detailed reservation information
     */
    private function getReservationDetails(array $reportData): array
    {
        $details = [];

        foreach ($reportData as $item) {
            if (!empty($item['reservations'])) {
                $details[] = [
                    'item_name' => $item['item_name'],
                    'item_sku' => $item['item_sku'],
                    'warehouse' => $item['warehouse'],
                    'reserved_quantity' => $item['reserved_quantity'],
                    'reservations' => $item['reservations'],
                ];
            }
        }

        return $details;
    }

    /**
     * Export data as CSV
     */
    private function exportCsv(array $reportData, bool $withReservations): Response
    {
        $filename = 'stock_report_' . date('Y-m-d_H-i-s') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($reportData, $withReservations) {
            $file = fopen('php://output', 'w');
            
            // Write headers
            if ($withReservations) {
                fputcsv($file, [
                    'Item Name', 'SKU', 'Warehouse', 'Total Quantity', 'Reserved Quantity',
                    'Available Quantity', 'Reorder Level', 'Is Low Stock', 'Unit Cost', 'Total Value'
                ]);
            } else {
                fputcsv($file, [
                    'Item Name', 'SKU', 'Warehouse', 'Quantity', 'Reorder Level', 
                    'Is Low Stock', 'Unit Cost', 'Total Value'
                ]);
            }

            // Write data
            foreach ($reportData as $item) {
                if ($withReservations) {
                    fputcsv($file, [
                        $item['item_name'],
                        $item['item_sku'],
                        $item['warehouse'],
                        $item['total_quantity'],
                        $item['reserved_quantity'],
                        $item['available_quantity'],
                        $item['reorder_level'],
                        $item['is_low_stock'] ? 'Yes' : 'No',
                        $item['unit_cost'],
                        $item['total_value'],
                    ]);
                } else {
                    fputcsv($file, [
                        $item['item_name'],
                        $item['item_sku'],
                        $item['warehouse'],
                        $item['total_quantity'],
                        $item['reorder_level'],
                        $item['is_low_stock'] ? 'Yes' : 'No',
                        $item['unit_cost'],
                        $item['total_value'],
                    ]);
                }
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export data as JSON
     */
    private function exportJson(array $reportData, bool $withReservations): Response
    {
        $filename = 'stock_report_' . date('Y-m-d_H-i-s') . '.json';
        
        $data = [
            'generated_at' => now()->toISOString(),
            'with_reservations' => $withReservations,
            'total_items' => count($reportData),
            'data' => $reportData,
        ];

        $headers = [
            'Content-Type' => 'application/json',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        return response(json_encode($data, JSON_PRETTY_PRINT), 200, $headers);
    }
} 