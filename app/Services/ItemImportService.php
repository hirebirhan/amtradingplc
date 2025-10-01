<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Category;
use App\Models\Item;
use App\Models\Stock;
use App\Models\StockHistory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ItemImportService
{
    public function getPreviewData(string $filePath): array
    {
        $reader = IOFactory::createReaderForFile($filePath);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($filePath);
        $sheet = $spreadsheet->getActiveSheet();

        $highestRow = (int) $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();
        $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);

        $branchHeaders = [];
        for ($col = 1; $col <= $highestColumnIndex; $col++) {
            $branchHeaders[$col] = trim((string) $sheet->getCellByColumnAndRow($col, 1)->getValue());
        }

        $itemHeaders = [];
        for ($col = 1; $col <= $highestColumnIndex; $col++) {
            $itemHeaders[] = trim((string) $sheet->getCellByColumnAndRow($col, 2)->getValue());
        }

        $allItems = $this->getAllItemsAsJson($sheet, $highestRow, $highestColumnIndex, $itemHeaders, $branchHeaders);

        return [
            'sheetTitle' => $sheet->getTitle(),
            'rowCount' => count($allItems),
            'headers' => $itemHeaders,
            'allItems' => $allItems,
        ];
    }

    public function applyJsonImport(array $itemData, ?int $defaultCategoryId): array
    {
        if (empty($itemData)) {
            return ['created' => 0, 'updated' => 0, 'stockAdjusted' => 0, 'errors' => ['No item data provided.']];
        }

        $created = 0;
        $updated = 0;
        $stockAdjusted = 0;
        $errors = [];

        DB::beginTransaction();

        try {
            foreach ($itemData as $data) {
                if (!is_array($data) || empty($data['name'])) {
                    $errors[] = 'Invalid item data found in JSON';
                    continue;
                }

                $branches = $data['branches'] ?? [];
                unset($data['branches']);

                if (empty($data['sku'])) {
                    $data['sku'] = $this->generateSku($data['name']);
                }

                $item = Item::firstOrNew(['sku' => $data['sku']]);
                $isNew = !$item->exists;

                $item->fill($data);

                if ($defaultCategoryId > 0) {
                    $item->category_id = $defaultCategoryId;
                } elseif (empty($item->category_id)) {
                    $item->category_id = Category::value('id'); // Fallback
                }
                
                $item->is_active = true;
                $item->save();

                $isNew ? $created++ : $updated++;

                if (!empty($branches)) {
                    $stockService = new StockMovementService();
                    foreach ($branches as $branchName => $quantity) {
                        if (empty($quantity)) continue;

                        $branch = Branch::whereRaw('LOWER(name) = ?', [strtolower($branchName)])->first();
                        if (!$branch) continue;

                        $warehouse = $stockService->ensureBranchWarehouse($branch->id);
                        $stock = Stock::firstOrNew(['warehouse_id' => $warehouse->id, 'item_id' => $item->id]);
                        $oldQuantity = $stock->exists ? $stock->quantity : 0;
                        
                        if ($oldQuantity != $quantity) {
                            $stock->quantity = $quantity;
                            $stock->save();

                            StockHistory::create([
                                'warehouse_id' => $warehouse->id,
                                'item_id' => $item->id,
                                'quantity_before' => $oldQuantity,
                                'quantity_after' => $quantity,
                                'quantity_change' => $quantity - $oldQuantity,
                                'reference_type' => 'import',
                                'reference_id' => 0,
                                'description' => 'JSON import stock adjustment',
                                'user_id' => auth()->id() ?? 0,
                            ]);
                            $stockAdjusted++;
                        }
                    }
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('JSON import failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            throw $e; // Re-throw to be caught by the component
        }

        return compact('created', 'updated', 'stockAdjusted', 'errors');
    }

    private function getAllItemsAsJson($sheet, $highestRow, $highestColumnIndex, $itemHeaders, $branchHeaders): array
    {
        $items = [];
        $headerMap = $this->mapHeaders($itemHeaders);

        $branchColumns = [];
        $currentBranch = '';
        foreach ($branchHeaders as $col => $header) {
            if (!empty($header)) {
                $currentBranch = strtolower(trim($header));
            }
            if (!empty($currentBranch)) {
                $branchColumns[$col] = $currentBranch;
            }
        }

        for ($row = 3; $row <= $highestRow; $row++) {
            $rowData = [];
            for ($col = 1; $col <= $highestColumnIndex; $col++) {
                $rowData[$col] = $sheet->getCellByColumnAndRow($col, $row)->getFormattedValue();
            }

            if (count(array_filter($rowData)) === 0) continue;

            $get = fn($key) => isset($headerMap[$key]) ? trim($rowData[$headerMap[$key]]) : null;

            $name = $get('name');
            if (empty($name)) continue;

            $sku = $get('sku') ?? $this->generateSku($name, $row);
            $cost = (float)str_replace(',', '', $get('cost_price') ?? '0');
            $unitQuantity = (int)($get('unit_quantity') ?? '1');
            if ($unitQuantity <= 0) $unitQuantity = 1;

            $costPricePerUnit = $cost;
            $sellingPricePerUnit = $cost;
            $costPrice = $cost * $unitQuantity;
            $sellingPrice = $sellingPricePerUnit * $unitQuantity;

            $branchQuantities = [];
            foreach ($itemHeaders as $colIndex => $header) {
                $normalizedHeader = strtolower(trim($header));
                if (in_array($normalizedHeader, ['quantity', 'qty'])) {
                    $col = $colIndex + 1;
                    if (isset($branchColumns[$col])) {
                        $branchName = $branchColumns[$col];
                        $quantity = (float)($rowData[$col] ?? 0);
                        if ($quantity > 0) {
                            $branchQuantities[$branchName] = $quantity;
                        }
                    }
                }
            }

            $items[] = [
                'name' => $name,
                'sku' => $sku,
                'barcode' => $get('barcode') ?? $sku,
                'cost_price' => $costPrice,
                'selling_price' => $sellingPrice,
                'cost_price_per_unit' => $costPricePerUnit,
                'selling_price_per_unit' => $sellingPricePerUnit,
                'reorder_level' => (int)($get('reorder_level') ?? 10),
                'unit' => 'pcs',
                'unit_quantity' => $unitQuantity,
                'brand' => $get('brand') ?? '',
                'description' => $get('description') ?? '',
                'is_active' => true,
                'created_by' => auth()->id() ?? 1,
                'branches' => $branchQuantities,
            ];
        }

        return $items;
    }

    private function mapHeaders(array $headers): array
    {
        $map = [];
        $definitions = [
            'name' => ['name', 'item', 'designation'],
            'sku' => ['code', 'sku'],
            'barcode' => ['barcode'],
            'category' => ['category'],
            'unit' => ['unit'],
            'unit_quantity' => ['um', 'u.m', 'unit quantity', 'pack size'],
            'cost_price' => ['ucost', 'u.cost', 'unitcost', 'cost'],
            'selling_price' => ['price', 'selling price'],
            'reorder_level' => ['reorder level'],
            'brand' => ['brand'],
            'description' => ['description'],
            'bicha' => ['bicha'],
            'kemer' => ['kemer'],
            'furi' => ['furi'],
        ];

        foreach ($headers as $index => $header) {
            $normalizedHeader = strtolower(trim($header));
            foreach ($definitions as $key => $aliases) {
                if (in_array($normalizedHeader, $aliases)) {
                    $map[$key] = $index + 1;
                    break;
                }
            }
        }
        return $map;
    }

    private function generateSku(string $name, ?int $row = null): string
    {
        $base = strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $name), 0, 10));
        return $base . '-' . ($row ?? rand(1000, 9999));
    }
}
