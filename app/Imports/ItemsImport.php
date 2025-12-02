<?php

namespace App\Imports;

use App\Models\Item;
use App\Models\Category;
use App\Models\Stock;
use App\Models\Warehouse;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;

class ItemsImport implements ToCollection, WithHeadingRow, WithValidation, WithBatchInserts, WithChunkReading
{
    private $importedCount = 0;
    private $errorCount = 0;
    private $duplicateCount = 0;
    private $skippedCount = 0;
    private $errors = [];
    private $existingItems = [];
    private $categories = [];
    private $warehouses = [];
    private $defaultCategory = null;

    public function __construct($defaultCategory = null)
    {
        $this->defaultCategory = $defaultCategory;
        
        try {
            $this->existingItems = Item::select('id', 'name', 'sku')->get()->keyBy('name');
            $this->categories = Category::select('id', 'name')->get()->keyBy('name');
            $this->warehouses = Warehouse::all();
            
            Log::info('ItemsImport initialized', [
                'user_id' => auth()->id(),
                'existing_items' => $this->existingItems->count(),
                'categories' => $this->categories->count(),
                'warehouses' => $this->warehouses->count(),
                'default_category' => $this->defaultCategory
            ]);
        } catch (\Exception $e) {
            Log::error('Error initializing ItemsImport', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);
            
            $this->existingItems = collect();
            $this->categories = collect();
            $this->warehouses = collect();
        }
    }

    public function collection(Collection $rows)
    {
        try {
            Log::info('Starting collection processing', [
                'user_id' => auth()->id(),
                'rows_count' => $rows->count()
            ]);
            
            $processedNames = [];

            foreach ($rows as $index => $row) {
                $rowNumber = $index + 2;
                
                try {
                    if (empty($row['name']) && empty($row['sku'])) {
                        $this->skippedCount++;
                        continue;
                    }

                    if (empty($row['name'])) {
                        $this->addError($rowNumber, 'Item name is missing - please add a name for this item');
                        continue;
                    }

                    $name = trim($row['name']);
                    $sku = isset($row['sku']) ? trim($row['sku']) : null;
                    $categoryName = isset($row['category']) ? trim($row['category']) : null;
                    $unit = isset($row['unit']) ? trim($row['unit']) : 'pcs';
                    $unitQuantity = isset($row['unit_quantity']) ? (int)$row['unit_quantity'] : 1;
                    
                    $costPrice = isset($row['cost_price']) && $row['cost_price'] !== '' ? (float)$row['cost_price'] : 0;
                    $sellingPrice = isset($row['selling_price']) && $row['selling_price'] !== '' ? (float)$row['selling_price'] : 0;
                    
                    $reorderLevel = isset($row['reorder_level']) ? (int)$row['reorder_level'] : 10;
                    $brand = isset($row['brand']) ? trim($row['brand']) : null;
                    $description = isset($row['description']) ? trim($row['description']) : null;
                    $barcode = isset($row['barcode']) ? trim($row['barcode']) : null;

                    if (in_array(strtolower($name), array_map('strtolower', $processedNames))) {
                        $this->addError($rowNumber, "Duplicate item name in this file - please use a unique name: {$name}");
                        continue;
                    }

                    if ($this->existingItems->has($name)) {
                        $this->duplicateCount++;
                        $this->addError($rowNumber, "Item already exists in the system - please use a different name: {$name}");
                        continue;
                    }

                    if ($sku && Item::where('sku', $sku)->exists()) {
                        $this->addError($rowNumber, "SKU already exists in the system - please use a different SKU: {$sku}");
                        continue;
                    }

                    if ($barcode && Item::where('barcode', $barcode)->exists()) {
                        $this->addError($rowNumber, "Barcode already exists in the system - please use a different barcode: {$barcode}");
                        continue;
                    }

                    $categoryId = null;
                    if ($categoryName) {
                        $category = $this->categories->first(function($cat) use ($categoryName) {
                            return strtolower($cat->name) === strtolower($categoryName);
                        });
                        $categoryId = $category ? $category->id : null;
                    } elseif ($this->defaultCategory) {
                        $defaultCategory = $this->categories->first(function($cat) {
                            return strtolower($cat->name) === strtolower($this->defaultCategory);
                        });
                        $categoryId = $defaultCategory ? $defaultCategory->id : null;
                    }

                    if (!$sku) {
                        $sku = 'SKU-' . strtoupper(Str::random(8));
                    }

                    if (!$barcode) {
                        $barcode = 'BAR-' . strtoupper(Str::random(12));
                    }

                    $branchId = auth()->user()->isSuperAdmin() || auth()->user()->isGeneralManager() 
                        ? null 
                        : auth()->user()->branch_id;

                    $item = Item::create([
                        'name' => $name,
                        'sku' => $sku,
                        'barcode' => $barcode,
                        'category_id' => $categoryId,
                        'branch_id' => $branchId,
                        'unit' => $unit,
                        'unit_quantity' => $unitQuantity,
                        'cost_price' => $costPrice,
                        'selling_price' => $sellingPrice,
                        'reorder_level' => $reorderLevel,
                        'brand' => $brand,
                        'description' => $description,
                        'is_active' => true,
                        'created_by' => auth()->id(),
                    ]);

                    foreach ($this->warehouses as $warehouse) {
                        Stock::create([
                            'item_id' => $item->id,
                            'warehouse_id' => $warehouse->id,
                            'quantity' => 0,
                        ]);
                    }

                    $this->importedCount++;
                    $processedNames[] = $name;

                    Log::info('Item imported via Excel', [
                        'user_id' => auth()->id(),
                        'item_id' => $item->id,
                        'name' => $name,
                        'row' => $rowNumber
                    ]);

                } catch (\Exception $e) {
                    $this->errorCount++;
                    $this->addError($rowNumber, 'Import error: ' . $e->getMessage());
                    
                    Log::error('Error importing item via Excel', [
                        'user_id' => auth()->id(),
                        'row' => $rowNumber,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            Log::info('Collection processing completed', [
                'user_id' => auth()->id(),
                'imported' => $this->importedCount,
                'errors' => $this->errorCount,
                'duplicates' => $this->duplicateCount,
                'skipped' => $this->skippedCount
            ]);

            session(['item_import' => [
                'imported' => $this->importedCount,
                'errors' => $this->errorCount,
                'duplicates' => $this->duplicateCount,
                'skipped' => $this->skippedCount,
                'error_details' => $this->errors
            ]]);

        } catch (\Exception $e) {
            Log::error('Fatal error in collection method', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);
            
            session(['item_import' => [
                'imported' => 0,
                'errors' => 1,
                'duplicates' => 0,
                'skipped' => 0,
                'error_details' => ['Fatal error: ' . $e->getMessage()]
            ]]);
        }
    }

    private function addError($rowNumber, $message)
    {
        $this->errors[] = "Row {$rowNumber}: {$message}";
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255|unique:items,name',
            'sku' => 'nullable|string|max:100|unique:items,sku',
            'barcode' => 'nullable|string|max:100|unique:items,barcode',
            'category' => 'nullable|string|max:255',
            'unit' => 'nullable|string|max:50',
            'unit_quantity' => 'nullable|integer|min:1',
            'cost_price' => 'nullable|numeric|min:0',
            'selling_price' => 'nullable|numeric|min:0',
            'reorder_level' => 'nullable|integer|min:0',
            'brand' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
        ];
    }

    public function customValidationMessages()
    {
        return [
            'name.required' => 'Item name is required.',
            'name.max' => 'Item name cannot exceed 255 characters.',
            'name.unique' => 'Item name already exists.',
            'sku.max' => 'SKU cannot exceed 100 characters.',
            'sku.unique' => 'SKU already exists.',
            'barcode.max' => 'Barcode cannot exceed 100 characters.',
            'barcode.unique' => 'Barcode already exists.',
            'unit.max' => 'Unit cannot exceed 50 characters.',
            'unit_quantity.min' => 'Unit quantity must be at least 1.',
            'cost_price.min' => 'Cost price cannot be negative.',
            'selling_price.min' => 'Selling price cannot be negative.',
            'reorder_level.min' => 'Reorder level cannot be negative.',
            'brand.max' => 'Brand cannot exceed 255 characters.',
            'description.max' => 'Description cannot exceed 1000 characters.',
        ];
    }

    public function batchSize(): int
    {
        return 100;
    }

    public function chunkSize(): int
    {
        return 100;
    }
}