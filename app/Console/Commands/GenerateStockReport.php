<?php

namespace App\Console\Commands;

use App\Models\Item;
use App\Models\Stock;
use App\Models\StockReservation;
use App\Models\Warehouse;
use App\Models\Branch;
use App\Services\StockMovementService;
use Illuminate\Console\Command;

class GenerateStockReport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stock:report 
                            {--format=table : Output format (table, csv, json)}
                            {--location= : Filter by specific warehouse or branch ID}
                            {--location-type=warehouse : Location type (warehouse, branch)}
                            {--low-stock : Show only low stock items}
                            {--with-reservations : Include reservation details}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate comprehensive stock reports with reservations';

    private StockMovementService $stockMovementService;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->stockMovementService = new StockMovementService();
        
        $format = $this->option('format');
        $locationId = $this->option('location');
        $locationType = $this->option('location-type');
        $lowStockOnly = $this->option('low-stock');
        $withReservations = $this->option('with-reservations');

        $this->info("🔍 Generating stock report...");

        // Build query based on options
        $query = $this->buildStockQuery($locationId, $locationType, $lowStockOnly);
        $stocks = $query->get();

        if ($stocks->isEmpty()) {
            $this->warn("No stock records found matching the criteria.");
            return 0;
        }

        // Prepare report data
        $reportData = $this->prepareReportData($stocks, $withReservations);

        // Output report in requested format
        switch ($format) {
            case 'csv':
                $this->outputCsvReport($reportData);
                break;
            case 'json':
                $this->outputJsonReport($reportData);
                break;
            default:
                $this->outputTableReport($reportData, $withReservations);
                break;
        }

        $this->outputSummary($reportData);

        return 0;
    }

    /**
     * Build stock query based on options
     */
    private function buildStockQuery(?string $locationId, string $locationType, bool $lowStockOnly)
    {
        $query = Stock::with(['item', 'warehouse']);

        // Filter by location
        if ($locationId) {
            if ($locationType === 'warehouse') {
                $query->where('warehouse_id', $locationId);
            } elseif ($locationType === 'branch') {
                $branch = Branch::with('warehouses')->find($locationId);
                if ($branch) {
                    $query->whereIn('warehouse_id', $branch->warehouses->pluck('id'));
                }
            }
        }

        // Filter low stock items
        if ($lowStockOnly) {
            $query->whereHas('item', function ($q) {
                $q->whereRaw('reorder_level > 0');
            })->whereRaw('quantity < (SELECT reorder_level FROM items WHERE items.id = stocks.item_id)');
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
     * Output table format report
     */
    private function outputTableReport(array $reportData, bool $withReservations): void
    {
        if ($withReservations) {
            $headers = [
                'Item', 'SKU', 'Warehouse', 'Total Qty', 'Reserved', 'Available', 
                'Reorder Level', 'Status', 'Unit Cost', 'Total Value'
            ];
        } else {
            $headers = [
                'Item', 'SKU', 'Warehouse', 'Quantity', 'Reorder Level', 
                'Status', 'Unit Cost', 'Total Value'
            ];
        }

        $rows = [];
        foreach ($reportData as $item) {
            $status = $item['is_low_stock'] ? '⚠️  LOW' : '✅ OK';
            
            if ($withReservations) {
                $rows[] = [
                    $item['item_name'],
                    $item['item_sku'],
                    $item['warehouse'],
                    number_format($item['total_quantity'], 2),
                    number_format($item['reserved_quantity'], 2),
                    number_format($item['available_quantity'], 2),
                    $item['reorder_level'],
                    $status,
                    '$' . number_format($item['unit_cost'], 2),
                    '$' . number_format($item['total_value'], 2),
                ];
            } else {
                $rows[] = [
                    $item['item_name'],
                    $item['item_sku'],
                    $item['warehouse'],
                    number_format($item['total_quantity'], 2),
                    $item['reorder_level'],
                    $status,
                    '$' . number_format($item['unit_cost'], 2),
                    '$' . number_format($item['total_value'], 2),
                ];
            }
        }

        $this->table($headers, $rows);

        // Show reservation details if requested
        if ($withReservations) {
            $this->showReservationDetails($reportData);
        }
    }

    /**
     * Show detailed reservation information
     */
    private function showReservationDetails(array $reportData): void
    {
        $hasReservations = false;
        
        foreach ($reportData as $item) {
            if (!empty($item['reservations'])) {
                if (!$hasReservations) {
                    $this->line("\n📋 Reservation Details:");
                    $hasReservations = true;
                }
                
                $this->line("\n🔹 {$item['item_name']} ({$item['item_sku']}) at {$item['warehouse']}:");
                
                foreach ($item['reservations'] as $reservation) {
                    $this->line("  - {$reservation['reference']}: {$reservation['quantity']} units");
                    $this->line("    Expires: {$reservation['expires_at']} | By: {$reservation['created_by']}");
                }
            }
        }
        
        if (!$hasReservations) {
            $this->line("\n✅ No active reservations found.");
        }
    }

    /**
     * Output CSV format report
     */
    private function outputCsvReport(array $reportData): void
    {
        $filename = 'stock_report_' . date('Y-m-d_H-i-s') . '.csv';
        $filepath = storage_path('app/reports/' . $filename);
        
        // Create reports directory if it doesn't exist
        if (!is_dir(dirname($filepath))) {
            mkdir(dirname($filepath), 0755, true);
        }

        $file = fopen($filepath, 'w');
        
        // Write headers
        fputcsv($file, [
            'Item Name', 'SKU', 'Warehouse', 'Total Quantity', 'Reserved Quantity',
            'Available Quantity', 'Reorder Level', 'Is Low Stock', 'Unit Cost', 'Total Value'
        ]);

        // Write data
        foreach ($reportData as $item) {
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
        }

        fclose($file);
        
        $this->info("📁 CSV report saved to: {$filepath}");
    }

    /**
     * Output JSON format report
     */
    private function outputJsonReport(array $reportData): void
    {
        $this->line(json_encode($reportData, JSON_PRETTY_PRINT));
    }

    /**
     * Output summary statistics
     */
    private function outputSummary(array $reportData): void
    {
        $totalItems = count($reportData);
        $lowStockItems = array_filter($reportData, fn($item) => $item['is_low_stock']);
        $totalValue = array_sum(array_column($reportData, 'total_value'));
        $totalReserved = array_sum(array_column($reportData, 'reserved_quantity'));

        $this->line("\n📊 Summary:");
        $this->line("• Total Items: {$totalItems}");
        $this->line("• Low Stock Items: " . count($lowStockItems));
        $this->line("• Total Inventory Value: $" . number_format($totalValue, 2));
        $this->line("• Total Reserved Quantity: " . number_format($totalReserved, 2));
        
        if ($totalReserved > 0) {
            $reservationPercent = ($totalReserved / array_sum(array_column($reportData, 'total_quantity'))) * 100;
            $this->line("• Reservation Rate: " . number_format($reservationPercent, 1) . "%");
        }
    }
} 