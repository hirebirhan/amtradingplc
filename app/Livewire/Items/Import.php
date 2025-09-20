<?php

namespace App\Livewire\Items;

use App\Models\Category;
use App\Models\Item;
use App\Models\Stock;
use App\Models\Warehouse;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\Attributes\Layout;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

#[Layout('components.layouts.app')]
class Import extends Component
{
    use WithFileUploads, AuthorizesRequests;

    public $importFile;
    public $importing = false;
    public $previewData = [];
    public $showPreview = false;
    public $previewErrors = [];
    public $defaultCategory = '';
    public $categories = [];
    public $warehouses = [];
    public $branchQuantities = [];
    public $importResults = [];
    public $allItemsExist = false;

    protected $rules = [
        'importFile' => 'nullable|file|mimes:xlsx,xls,csv|max:10240',
    ];

    public function mount()
    {
        if (!auth()->user()->can('items.create')) {
            abort(403, 'You do not have permission to import items.');
        }
        
        $this->categories = Category::where('is_active', true)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();

        $this->warehouses = Warehouse::where('is_active', true)->orderBy('name')->get();
        
        if (!empty($this->categories)) {
            $this->defaultCategory = reset($this->categories);
        }
    }

    public function updatedImportFile()
    {
        Log::info('Import file selected', [
            'user_id' => Auth::id(),
            'filename' => $this->importFile ? $this->importFile->getClientOriginalName() : null,
            'size' => $this->importFile ? $this->importFile->getSize() : 0
        ]);
        
        $this->reset(['previewData', 'showPreview', 'previewErrors', 'importResults']);
    }

    public function clearFile()
    {
        Log::info('Import file cleared', ['user_id' => Auth::id()]);
        $this->reset(['importFile', 'previewData', 'showPreview', 'previewErrors', 'importResults', 'allItemsExist']);
    }

    public function previewImport()
    {
        try {
            if (!$this->importFile) {
                $this->dispatch('notify', type: 'error', message: 'Please select a file first.');
                return;
            }

            Log::info('Starting import preview', [
                'user_id' => Auth::id(),
                'filename' => $this->importFile->getClientOriginalName(),
                'size' => $this->importFile->getSize()
            ]);

            $this->previewData = [];
            $this->previewErrors = [];
            
            try {
                $rows = Excel::toCollection(new \App\Imports\ItemsImport, $this->importFile);
            } catch (\Exception $e) {
                Log::error('Excel import error', [
                    'user_id' => Auth::id(),
                    'filename' => $this->importFile->getClientOriginalName(),
                    'error' => $e->getMessage()
                ]);
                
                if (strpos($e->getMessage(), 'Class') !== false && strpos($e->getMessage(), 'not found') !== false) {
                    $this->dispatch('notify', type: 'error', message: 'Excel package not properly configured. Please contact administrator.');
                } else {
                    $this->dispatch('notify', type: 'error', message: 'Error reading file: ' . $e->getMessage());
                }
                return;
            }
            
            if ($rows->isEmpty()) {
                Log::warning('Empty import file', [
                    'user_id' => Auth::id(),
                    'filename' => $this->importFile->getClientOriginalName()
                ]);
                $this->dispatch('notify', type: 'error', message: 'No data found in the file.');
                return;
            }

            $existingItems = Item::select('id', 'name', 'sku', 'barcode')->get();
            $existingNames = $existingItems->pluck('name')->toArray();
            $existingSkus = $existingItems->pluck('sku')->toArray();
            $existingBarcodes = $existingItems->pluck('barcode')->toArray();
            $categories = Category::pluck('name')->toArray();

            $processedNames = [];

            foreach ($rows[0] as $index => $row) {
                try {
                    $rowNumber = $index + 2;
                    
                    if (empty($row['name']) && empty($row['sku'])) {
                        continue;
                    }

                    $name = isset($row['name']) ? trim($row['name']) : '';
                    $sku = isset($row['sku']) ? trim($row['sku']) : '';
                    $barcode = isset($row['barcode']) ? trim($row['barcode']) : '';
                    
                    $categoryName = isset($row['category']) ? trim($row['category']) : '';
                    if (empty($categoryName) && !empty($this->defaultCategory)) {
                        $categoryName = $this->defaultCategory;
                    }
                    
                    $unit = isset($row['unit']) ? trim($row['unit']) : 'pcs';
                    $unitQuantity = isset($row['unit_quantity']) ? (int)$row['unit_quantity'] : 1;
                    
                    $costPrice = isset($row['cost_price']) && $row['cost_price'] !== '' ? (float)$row['cost_price'] : 0;
                    $sellingPrice = isset($row['selling_price']) && $row['selling_price'] !== '' ? (float)$row['selling_price'] : 0;
                    
                    $reorderLevel = isset($row['reorder_level']) ? (int)$row['reorder_level'] : 10;
                    $brand = isset($row['brand']) ? trim($row['brand']) : '';
                    $description = isset($row['description']) ? trim($row['description']) : '';

                    $errors = [];
                    
                    if (empty($name)) {
                        $errors[] = 'Item name is missing - please add a name for this item';
                    } elseif (strlen($name) > 255) {
                        $errors[] = 'Item name is too long (max 255 characters) - please shorten the name';
                    }

                    if (in_array(strtolower($name), array_map('strtolower', $processedNames))) {
                        $errors[] = 'Duplicate item name in this file - please use a unique name';
                    }
                    if (in_array(strtolower($name), array_map('strtolower', $existingNames))) {
                        $errors[] = 'Item already exists in the system - please use a different name';
                    }

                    if (!empty($sku)) {
                        if (strlen($sku) > 100) {
                            $errors[] = 'SKU is too long (max 100 characters) - please shorten the SKU';
                        }
                        if (in_array($sku, $existingSkus)) {
                            $errors[] = 'SKU already exists in the system - please use a different SKU';
                        }
                    }

                    if (!empty($barcode)) {
                        if (strlen($barcode) > 100) {
                            $errors[] = 'Barcode is too long (max 100 characters) - please shorten the barcode';
                        }
                        if (in_array($barcode, $existingBarcodes)) {
                            $errors[] = 'Barcode already exists in the system - please use a different barcode';
                        }
                    }

                    $finalCategoryName = $categoryName;
                    $finalCategoryId = null;

                    $categoryModel = Category::where('name', $categoryName)->first();
                    if ($categoryModel) {
                        $finalCategoryId = $categoryModel->id;
                    } elseif (!empty($this->defaultCategory)) {
                        $defaultCategoryModel = Category::where('name', $this->defaultCategory)->first();
                        if ($defaultCategoryModel) {
                            $finalCategoryName = $defaultCategoryModel->name;
                            $finalCategoryId = $defaultCategoryModel->id;
                        }
                    }

                    if (empty($finalCategoryName)) {
                        $errors[] = 'Category not found and no default set - please select a valid category or set a default';
                    }

                    if (strlen($unit) > 50) {
                        $errors[] = 'Unit is too long (max 50 characters) - please shorten the unit';
                    }

                    if ($unitQuantity < 1) {
                        $errors[] = 'Unit quantity must be at least 1 - please enter a valid quantity';
                    }

                    if ($costPrice < 0) {
                        $errors[] = 'Cost price cannot be negative - please enter a positive number or 0';
                    }
                    if ($sellingPrice < 0) {
                        $errors[] = 'Selling price cannot be negative - please enter a positive number or 0';
                    }

                    if ($reorderLevel < 0) {
                        $errors[] = 'Reorder level cannot be negative - please enter a positive number or 0';
                    }

                    if (strlen($brand) > 255) {
                        $errors[] = 'Brand name is too long (max 255 characters) - please shorten the brand name';
                    }

                    if (strlen($description) > 1000) {
                        $errors[] = 'Description is too long (max 1000 characters) - please shorten the description';
                    }

                    $this->previewData[] = [
                        'row' => $rowNumber,
                        'name' => $name,
                        'sku' => $sku,
                        'barcode' => $barcode,
                        'category' => $finalCategoryName,
                        'unit' => $unit,
                        'unit_quantity' => $unitQuantity,
                        'cost_price' => $costPrice,
                        'selling_price' => $sellingPrice,
                        'reorder_level' => $reorderLevel,
                        'brand' => $brand,
                        'description' => $description,
                        'category_id' => $finalCategoryId,
                        'errors' => $errors,
                        'is_valid' => empty($errors)
                    ];

                    if (empty($errors)) {
                        foreach ($this->warehouses as $warehouse) {
                            $this->branchQuantities[$rowNumber][$warehouse->id] = 0;
                        }
                    }

                    if (!empty($errors)) {
                        $this->previewErrors[] = "Row {$rowNumber}: " . implode(', ', $errors);
                    }

                    $processedNames[] = $name;

                } catch (\Exception $e) {
                    Log::error('Error processing preview row', [
                        'user_id' => Auth::id(),
                        'row' => $index + 2,
                        'error' => $e->getMessage()
                    ]);
                    $this->previewErrors[] = "Row " . ($index + 2) . ": Error processing row - " . $e->getMessage();
                }
            }

            Log::info('Import preview completed', [
                'user_id' => Auth::id(),
                'total_rows' => count($this->previewData),
                'valid_rows' => count(array_filter($this->previewData, fn($row) => $row['is_valid'])),
                'error_rows' => count(array_filter($this->previewData, fn($row) => !$row['is_valid']))
            ]);

            // Check if all items already exist in the system
            $allItemsExist = count($this->previewData) > 0 && 
                           count(array_filter($this->previewData, fn($row) => 
                               in_array('Item already exists in the system', $row['errors'] ?? [])
                           )) === count($this->previewData);

            if ($allItemsExist) {
                $this->allItemsExist = true;
                $this->showPreview = true;
                $this->dispatch('notify', type: 'info', message: 'All items in your Excel file already exist in the system. No new items to import.');
                return;
            }
            
            $this->allItemsExist = false;
            $this->showPreview = true;
        }
    }

    public function cancelPreview()
    {
        Log::info('Import preview cancelled', ['user_id' => Auth::id()]);
        $this->reset(['previewData', 'showPreview', 'previewErrors', 'allItemsExist']);
    }

    public function importItems()
    {
        if (!Auth::user()->can('create', Item::class)) {
            Log::warning('Unauthorized import attempt', ['user_id' => Auth::id()]);
            $this->dispatch('notify', type: 'error', message: 'You are not authorized to create items.');
            return;
        }

        if (empty($this->previewData)) {
            Log::warning('Import attempted without preview data', ['user_id' => Auth::id()]);
            $this->dispatch('notify', type: 'error', message: 'No data to import. Please preview the file first.');
            return;
        }

        Log::info('Starting item import', [
            'user_id' => Auth::id(),
            'filename' => $this->importFile->getClientOriginalName(),
            'total_rows' => count($this->previewData),
            'default_category' => $this->defaultCategory
        ]);

        $this->importing = true;
        $this->importResults = [
            'imported' => 0,
            'errors' => 0,
            'duplicates' => 0,
            'skipped' => 0,
            'error_details' => []
        ];

        try {
            DB::beginTransaction();

            $existingItems = Item::select('id', 'name', 'sku', 'barcode')->get();
            $existingNames = $existingItems->pluck('name')->toArray();
            $existingSkus = $existingItems->pluck('sku')->toArray();
            $existingBarcodes = $existingItems->pluck('barcode')->toArray();
            $categories = Category::select('id', 'name')->get()->keyBy('name');
            $warehouses = Warehouse::all();

            $processedNames = [];

            foreach ($this->previewData as $itemData) {
                try {
                    if (!$itemData['is_valid']) {
                        $this->importResults['errors']++;
                        $this->importResults['error_details'][] = "Row {$itemData['row']}: " . implode(', ', $itemData['errors']);
                        continue;
                    }

                    $name = $itemData['name'];
                    $sku = $itemData['sku'];
                    $barcode = $itemData['barcode'];
                    $categoryId = $itemData['category_id'];

                    if (in_array(strtolower($name), array_map('strtolower', $processedNames))) {
                        $this->importResults['errors']++;
                        $this->importResults['error_details'][] = "Row {$itemData['row']}: Duplicate name in import";
                        continue;
                    }

                    if (in_array(strtolower($name), array_map('strtolower', $existingNames))) {
                        $this->importResults['duplicates']++;
                        $this->importResults['error_details'][] = "Row {$itemData['row']}: Item already exists in database";
                        continue;
                    }

                    if (!empty($sku) && in_array($sku, $existingSkus)) {
                        $this->importResults['errors']++;
                        $this->importResults['error_details'][] = "Row {$itemData['row']}: SKU already exists";
                        continue;
                    }

                    if (!empty($barcode) && in_array($barcode, $existingBarcodes)) {
                        $this->importResults['errors']++;
                        $this->importResults['error_details'][] = "Row {$itemData['row']}: Barcode already exists";
                        continue;
                    }

                    if (empty($sku)) {
                        $sku = $this->generateUniqueSku();
                    }

                    if (empty($barcode)) {
                        $barcode = $this->generateUniqueBarcode();
                    }

                    $itemDataToSave = [
                        'name' => $name,
                        'sku' => $sku,
                        'barcode' => $barcode,
                        'category_id' => $categoryId,
                        'unit' => $itemData['unit'],
                        'unit_quantity' => $itemData['unit_quantity'],
                        'cost_price' => $itemData['cost_price'],
                        'selling_price' => $itemData['selling_price'],
                        'reorder_level' => $itemData['reorder_level'],
                        'brand' => $itemData['brand'],
                        'description' => $itemData['description'],
                        'is_active' => true,
                        'created_by' => Auth::id(),
                    ];

                    $item = Item::create($itemDataToSave);

                    if (isset($this->branchQuantities[$itemData['row']])) {
                        foreach ($this->branchQuantities[$itemData['row']] as $warehouseId => $quantity) {
                            if ($quantity > 0) {
                                Stock::create([
                                    'item_id' => $item->id,
                                    'warehouse_id' => $warehouseId,
                                    'quantity' => $quantity,
                                ]);
                            }
                        }
                    }

                    $this->importResults['imported']++;
                    $processedNames[] = $name;

                    $existingNames[] = $name;
                    $existingSkus[] = $sku;
                    $existingBarcodes[] = $barcode;

                    Log::info('Item imported successfully', [
                        'user_id' => Auth::id(),
                        'item_id' => $item->id,
                        'name' => $name,
                        'row' => $itemData['row']
                    ]);

                } catch (\Exception $e) {
                    $this->importResults['errors']++;
                    $this->importResults['error_details'][] = "Row {$itemData['row']}: " . $e->getMessage();
                    
                    Log::error('Error importing item', [
                        'user_id' => Auth::id(),
                        'row' => $itemData['row'],
                        'error' => $e->getMessage()
                    ]);
                }
            }

            DB::commit();

            Log::info('Import process completed', [
                'user_id' => Auth::id(),
                'results' => $this->importResults
            ]);

            if ($this->importResults['imported'] > 0) {
                $message = $this->buildSuccessMessage();
                $this->dispatch('notify', type: 'success', message: $message);
                
                $this->reset(['importFile', 'previewData', 'showPreview', 'previewErrors', 'branchQuantities']);
                // Use dispatch to handle redirects through JavaScript instead of direct redirect
                $this->dispatch('itemsImportedSuccessfully', []);
                // Add script in the blade file to handle the redirect event
            } else {
                $this->dispatch('notify', type: 'error', message: 'No items were imported. Please check the validation errors and try again.');
            }

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Fatal error in import process', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);
            
            $this->dispatch('notify', type: 'error', message: 'Error importing items: ' . $e->getMessage());
        } finally {
            $this->importing = false;
        }
    }

    private function generateUniqueSku()
    {
        $maxAttempts = 10;
        $attempts = 0;
        
        do {
            $randomString = strtoupper(Str::random(6));
            $sku = $randomString;
            
            if (!Item::where('sku', $sku)->exists()) {
                return $sku;
            }
            
            $attempts++;
        } while ($attempts < $maxAttempts);
        
        return 'SKU-' . time() . '-' . strtoupper(Str::random(4));
    }

    private function generateUniqueBarcode()
    {
        $maxAttempts = 10;
        $attempts = 0;
        
        do {
            $randomString = strtoupper(Str::random(8));
            $barcode = $randomString;
            
            if (!Item::where('barcode', $barcode)->exists()) {
                return $barcode;
            }
            
            $attempts++;
        } while ($attempts < $maxAttempts);
        
        return 'BAR-' . time() . '-' . strtoupper(Str::random(4));
    }

    private function buildSuccessMessage()
    {
        $message = "✅ Import completed successfully!\n\n";
        $message .= "📊 Results:\n";
        $message .= "• Imported: {$this->importResults['imported']} item" . ($this->importResults['imported'] != 1 ? 's' : '') . "\n";
        $message .= "• Errors: {$this->importResults['errors']} row" . ($this->importResults['errors'] != 1 ? 's' : '') . "\n";
        $message .= "• Duplicates: {$this->importResults['duplicates']} item" . ($this->importResults['duplicates'] != 1 ? 's' : '') . "\n";
        $message .= "• Skipped: {$this->importResults['skipped']} empty row" . ($this->importResults['skipped'] != 1 ? 's' : '') . "\n";

        if (!empty($this->importResults['error_details'])) {
            $message .= "\n❌ Errors found:\n";
            foreach (array_slice($this->importResults['error_details'], 0, 5) as $error) {
                $message .= "• {$error}\n";
            }
            if (count($this->importResults['error_details']) > 5) {
                $message .= "• ... and " . (count($this->importResults['error_details']) - 5) . " more errors\n";
            }
        }

        return $message;
    }

    public function render()
    {
        return view('livewire.items.import')
            ->title('Import Items');
    }
} 