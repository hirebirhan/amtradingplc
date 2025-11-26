<?php

namespace App\Livewire\Items;

use App\Models\Category;
use App\Models\Item;
use App\Imports\ItemsImport;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Warehouse;
use App\Models\Stock;
use App\Models\Branch;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

#[Layout('components.layouts.app')]
class Index extends Component
{
    use WithPagination, WithFileUploads, AuthorizesRequests;

    #[Url(except: '')]
    public $search = '';
    
    #[Url(except: '')]
    public $categoryFilter = '';
    
    #[Url(except: '')]
    public $stockFilter = '';
    
    #[Url(except: '')]
    public $branchFilter = '';
    
    #[Url(except: '')]
    public $warehouseFilter = '';
    
    #[Url(except: false)]
    public $hideZeroStock = false;
    
    #[Url(except: 10)]
    public $perPage = 10;
    
    #[Url(except: 'name')]
    public $sortField = 'name';
    
    #[Url(except: 'asc')]
    public $sortDirection = 'asc';
    
    public $importFile;
    public $importing = false;
    public $previewData = [];
    public $showPreview = false;
    public $previewErrors = [];

    protected $rules = [
        'importFile' => 'nullable|file|mimes:xlsx,xls,csv|max:10240',
    ];

    /**
     * Initialize the component state.
     * This allows direct access to filtered views via URL parameters.
     */
    public function mount()
    {
        // The #[Url] attribute automatically handles URL parameters
        // We can add additional initialization logic here if needed
        session()->flash('low-stock-report-accessed', $this->stockFilter === 'low');
    }

    public function clearSearch()
    {
        $this->search = '';
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    /**
     * Sort by stock amount
     */
    public function sortByStock()
    {
        $this->sortField = 'stock';
        $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
    }

    public function delete(Item $item)
    {
        // Check permission
        if (!auth()->user()->can('items.delete')) {
            $this->dispatch('notify', type: 'error', message: 'You do not have permission to delete items.');
            return;
        }

        // Check if the item has stock history
        if ($item->stockHistories()->count() > 0) {
            $this->dispatch('notify', type: 'error', message: 'Cannot delete item with stock history records.');
            return;
        }

        // Check if the item has stock
        if ($item->stocks()->sum('quantity') > 0) {
            $this->dispatch('notify', type: 'error', message: 'Cannot delete item with existing stock. Please adjust stock to zero first.');
            return;
        }

        // Delete the item
        $item->delete();
        $this->dispatch('notify', type: 'success', message: 'Item deleted successfully!');
    }

    public function previewImport()
    {
        try {
            // Check permission
            if (!auth()->user()->can('items.create')) {
                $this->dispatch('notify', type: 'error', message: 'You do not have permission to import items.');
                return;
            }

            // Check if file is selected
            if (!$this->importFile) {
                $this->dispatch('notify', type: 'error', message: 'Please select a file first.');
                return;
            }

            // Reset preview data
            $this->previewData = [];
            $this->previewErrors = [];
            
            \Log::info('Starting import preview', ['file' => $this->importFile->getClientOriginalName()]);
            
            // Read the file and parse data
            try {
                $rows = \Maatwebsite\Excel\Facades\Excel::toCollection(new \App\Imports\ItemsImport, $this->importFile);
            } catch (\Exception $e) {
                \Log::error('Excel facade error', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                
                // Check if it's a class not found error
                if (strpos($e->getMessage(), 'Class') !== false && strpos($e->getMessage(), 'not found') !== false) {
                    $this->dispatch('notify', type: 'error', message: 'Excel package not properly configured. Please contact administrator.');
                } else {
                    $this->dispatch('notify', type: 'error', message: 'Error reading file: ' . $e->getMessage());
                }
                return;
            }
            
            \Log::info('File read successfully', ['rows_count' => $rows->count()]);
            
            if ($rows->isEmpty()) {
                $this->dispatch('notify', type: 'error', message: 'No data found in the file.');
                return;
            }

            // Get existing data for validation
            $existingItems = \App\Models\Item::select('id', 'name', 'sku', 'barcode')->get();
            $existingNames = $existingItems->pluck('name')->toArray();
            $existingSkus = $existingItems->pluck('sku')->toArray();
            $existingBarcodes = $existingItems->pluck('barcode')->toArray();
            $categories = \App\Models\Category::pluck('name')->toArray();

            \Log::info('Validation data loaded', [
                'existing_items' => $existingItems->count(),
                'categories' => count($categories)
            ]);

            $processedNames = [];

            foreach ($rows[0] as $index => $row) {
                try {
                    $rowNumber = $index + 2; // Excel rows start from 1, and we have header
                    
                    // Skip empty rows
                    if (empty($row['name']) && empty($row['sku'])) {
                        continue;
                    }

                    $name = isset($row['name']) ? trim($row['name']) : '';
                    $sku = isset($row['sku']) ? trim($row['sku']) : '';
                    $barcode = isset($row['barcode']) ? trim($row['barcode']) : '';
                    $categoryName = isset($row['category']) ? trim($row['category']) : '';
                    $unit = isset($row['unit']) ? trim($row['unit']) : 'pcs';
                    $unitQuantity = isset($row['unit_quantity']) ? (int)$row['unit_quantity'] : 1;
                    $costPrice = isset($row['cost_price']) ? (float)$row['cost_price'] : 0;
                    $sellingPrice = isset($row['selling_price']) ? (float)$row['selling_price'] : 0;
                    $reorderLevel = isset($row['reorder_level']) ? (int)$row['reorder_level'] : 10;
                    $brand = isset($row['brand']) ? trim($row['brand']) : '';
                    $description = isset($row['description']) ? trim($row['description']) : '';

                    // Validation
                    $errors = [];
                    
                    // Required field validation
                    if (empty($name)) {
                        $errors[] = 'Name is required';
                    } elseif (strlen($name) > 255) {
                        $errors[] = 'Name too long (max 255 characters)';
                    }

                    // Duplicate validation
                    if (in_array(strtolower($name), array_map('strtolower', $processedNames))) {
                        $errors[] = 'Duplicate name in import';
                    }
                    if (in_array(strtolower($name), array_map('strtolower', $existingNames))) {
                        $errors[] = 'Item already exists in database';
                    }

                    // SKU validation
                    if (!empty($sku)) {
                        if (strlen($sku) > 100) {
                            $errors[] = 'SKU too long (max 100 characters)';
                        }
                        if (in_array($sku, $existingSkus)) {
                            $errors[] = 'SKU already exists';
                        }
                    }

                    // Barcode validation
                    if (!empty($barcode)) {
                        if (strlen($barcode) > 100) {
                            $errors[] = 'Barcode too long (max 100 characters)';
                        }
                        if (in_array($barcode, $existingBarcodes)) {
                            $errors[] = 'Barcode already exists';
                        }
                    }

                    // Category validation
                    if (!empty($categoryName) && !in_array($categoryName, $categories)) {
                        $errors[] = 'Category not found in system';
                    }

                    // Unit validation
                    if (strlen($unit) > 50) {
                        $errors[] = 'Unit too long (max 50 characters)';
                    }

                    // Unit quantity validation
                    if ($unitQuantity < 1) {
                        $errors[] = 'Unit quantity must be at least 1';
                    }

                    // Price validation
                    if ($costPrice < 0) {
                        $errors[] = 'Cost price cannot be negative';
                    }
                    if ($sellingPrice < 0) {
                        $errors[] = 'Selling price cannot be negative';
                    }

                    // Reorder level validation
                    if ($reorderLevel < 0) {
                        $errors[] = 'Reorder level cannot be negative';
                    }

                    // Brand validation
                    if (strlen($brand) > 255) {
                        $errors[] = 'Brand too long (max 255 characters)';
                    }

                    // Description validation
                    if (strlen($description) > 1000) {
                        $errors[] = 'Description too long (max 1000 characters)';
                    }

                    $this->previewData[] = [
                        'row' => $rowNumber,
                        'name' => $name,
                        'sku' => $sku,
                        'barcode' => $barcode,
                        'category' => $categoryName,
                        'unit' => $unit,
                        'unit_quantity' => $unitQuantity,
                        'cost_price' => $costPrice,
                        'selling_price' => $sellingPrice,
                        'reorder_level' => $reorderLevel,
                        'brand' => $brand,
                        'description' => $description,
                        'errors' => $errors,
                        'is_valid' => empty($errors)
                    ];

                    if (!empty($errors)) {
                        $this->previewErrors[] = "Row {$rowNumber}: " . implode(', ', $errors);
                    }

                    $processedNames[] = $name;

                } catch (\Exception $e) {
                    \Log::error('Error processing row in preview', [
                        'row' => $index + 2,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    
                    $this->previewErrors[] = "Row " . ($index + 2) . ": Error processing row - " . $e->getMessage();
                }
            }

            \Log::info('Preview processing completed', [
                'total_rows' => count($this->previewData),
                'valid_rows' => count(array_filter($this->previewData, fn($row) => $row['is_valid'])),
                'error_rows' => count(array_filter($this->previewData, fn($row) => !$row['is_valid']))
            ]);

            $this->showPreview = true;

        } catch (\Exception $e) {
            \Log::error('Fatal error in previewImport', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            $this->dispatch('notify', type: 'error', message: 'Error reading file: ' . $e->getMessage());
        }
    }

    public function cancelPreview()
    {
        $this->reset(['previewData', 'showPreview', 'previewErrors', 'importFile']);
    }

    public function importItems()
    {
        try {
            // Check permission
            if (!auth()->user()->can('items.create')) {
                $this->dispatch('notify', type: 'error', message: 'You do not have permission to import items.');
                return;
            }

            $this->validate();

            \Log::info('Starting import process', ['file' => $this->importFile->getClientOriginalName()]);

            $this->importing = true;

            Excel::import(new ItemsImport, $this->importFile);

            $this->importing = false;
            $this->reset('importFile');

            // Get import results from session
            $importResults = session('item_import', [
                'imported' => 0, 
                'errors' => 0, 
                'duplicates' => 0, 
                'skipped' => 0,
                'error_details' => []
            ]);
            
            $imported = $importResults['imported'];
            $errors = $importResults['errors'];
            $duplicates = $importResults['duplicates'];
            $skipped = $importResults['skipped'];

            \Log::info('Import completed', [
                'imported' => $imported,
                'errors' => $errors,
                'duplicates' => $duplicates,
                'skipped' => $skipped
            ]);

            if ($imported > 0) {
                // Build detailed success message
                $message = "✅ Import completed successfully!\n\n";
                $message .= "📊 Results:\n";
                $message .= "• Imported: {$imported} item" . ($imported != 1 ? 's' : '') . "\n";
                $message .= "• Errors: {$errors} row" . ($errors != 1 ? 's' : '') . "\n";
                $message .= "• Duplicates: {$duplicates} item" . ($duplicates != 1 ? 's' : '') . "\n";
                $message .= "• Skipped: {$skipped} empty row" . ($skipped != 1 ? 's' : '') . "\n";

                if (!empty($importResults['error_details'])) {
                    $message .= "\n❌ Errors found:\n";
                    foreach (array_slice($importResults['error_details'], 0, 5) as $error) {
                        $message .= "• {$error}\n";
                    }
                    if (count($importResults['error_details']) > 5) {
                        $message .= "• ... and " . (count($importResults['error_details']) - 5) . " more errors\n";
                    }
                }

                $this->dispatch('notify', type: 'success', message: $message);
            } else {
                $this->dispatch('notify', type: 'error', message: 'No items were imported. Please check your file format and try again.');
            }
        } catch (\Exception $e) {
            \Log::error('Fatal error in importItems', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            $this->importing = false;
            $this->dispatch('notify', type: 'error', message: 'Error importing items: ' . $e->getMessage());
        }
    }

    public function updatedImportFile()
    {
        \Log::info('File selected', [
            'has_file' => $this->importFile ? 'yes' : 'no',
            'filename' => $this->importFile ? $this->importFile->getClientOriginalName() : 'none',
            'size' => $this->importFile ? $this->importFile->getSize() : 0,
            'mime' => $this->importFile ? $this->importFile->getMimeType() : 'none'
        ]);
        
        $this->reset(['previewData', 'showPreview', 'previewErrors']);
    }

    public function simpleFileTest()
    {
        try {
            if (!$this->importFile) {
                $this->dispatch('notify', type: 'error', message: 'No file selected');
                return;
            }
            
            $filename = $this->importFile->getClientOriginalName();
            $size = $this->importFile->getSize();
            $mime = $this->importFile->getMimeType();
            $extension = $this->importFile->getClientOriginalExtension();
            
            $message = "File selected successfully!\n";
            $message .= "Name: {$filename}\n";
            $message .= "Size: " . number_format($size / 1024, 2) . " KB\n";
            $message .= "Type: {$mime}\n";
            $message .= "Extension: {$extension}";
            
            \Log::info('Simple file test passed', [
                'filename' => $filename,
                'size' => $size,
                'mime' => $mime,
                'extension' => $extension
            ]);
            
            $this->dispatch('notify', type: 'success', message: $message);
            
        } catch (\Exception $e) {
            \Log::error('Simple file test failed', ['error' => $e->getMessage()]);
            $this->dispatch('notify', type: 'error', message: 'File test failed: ' . $e->getMessage());
        }
    }

    public function testExcel()
    {
        try {
            \Log::info('Testing Excel functionality');
            
            // Test if Excel facade is available
            if (!class_exists('Maatwebsite\Excel\Facades\Excel')) {
                throw new \Exception('Excel facade not found');
            }
            
            // Test if ItemsImport class exists
            if (!class_exists('App\Imports\ItemsImport')) {
                throw new \Exception('ItemsImport class not found');
            }
            
            // Test if Excel service provider is loaded
            $providers = app()->getLoadedProviders();
            if (!isset($providers['Maatwebsite\Excel\ExcelServiceProvider'])) {
                throw new \Exception('Excel service provider not loaded');
            }
            
            // Test if we can create an instance of ItemsImport
            $import = new \App\Imports\ItemsImport();
            if (!$import) {
                throw new \Exception('Could not create ItemsImport instance');
            }
            
            $this->dispatch('notify', type: 'success', message: 'Excel functionality is working correctly. All components loaded.');
            
        } catch (\Exception $e) {
            \Log::error('Excel test failed', ['error' => $e->getMessage()]);
            $this->dispatch('notify', type: 'error', message: 'Excel test failed: ' . $e->getMessage());
        }
    }

    public function fallbackImport()
    {
        try {
            if (!$this->importFile) {
                $this->dispatch('notify', type: 'error', message: 'No file selected');
                return;
            }
            
            \Log::info('Using fallback import method', [
                'filename' => $this->importFile->getClientOriginalName()
            ]);
            
            // Simple CSV parsing as fallback
            $file = $this->importFile;
            $handle = fopen($file->getPathname(), 'r');
            
            if (!$handle) {
                throw new \Exception('Could not open file');
            }
            
            $rows = [];
            $headers = null;
            $rowNumber = 0;
            
            while (($data = fgetcsv($handle)) !== false) {
                $rowNumber++;
                
                if ($rowNumber === 1) {
                    $headers = $data;
                    continue;
                }
                
                if (count($data) === count($headers)) {
                    $rows[] = array_combine($headers, $data);
                }
            }
            
            fclose($handle);
            
            \Log::info('Fallback import parsed rows', ['count' => count($rows)]);
            
            $this->dispatch('notify', type: 'success', message: 'Fallback import parsed ' . count($rows) . ' rows successfully.');
            
        } catch (\Exception $e) {
            \Log::error('Fallback import failed', ['error' => $e->getMessage()]);
            $this->dispatch('notify', type: 'error', message: 'Fallback import failed: ' . $e->getMessage());
        }
    }

    public function testFileUpload()
    {
        try {
            if (!$this->importFile) {
                $this->dispatch('notify', type: 'error', message: 'No file selected');
                return;
            }
            
            \Log::info('Testing file upload', [
                'filename' => $this->importFile->getClientOriginalName(),
                'size' => $this->importFile->getSize(),
                'mime' => $this->importFile->getMimeType(),
                'extension' => $this->importFile->getClientOriginalExtension()
            ]);
            
            $this->dispatch('notify', type: 'success', message: 'File upload test successful: ' . $this->importFile->getClientOriginalName());
            
        } catch (\Exception $e) {
            \Log::error('File upload test failed', ['error' => $e->getMessage()]);
            $this->dispatch('notify', type: 'error', message: 'File upload test failed: ' . $e->getMessage());
        }
    }

    public function showFileInfo()
    {
        if (!$this->importFile) {
            $this->dispatch('notify', type: 'error', message: 'No file selected');
            return;
        }
        
        $info = [
            'Name' => $this->importFile->getClientOriginalName(),
            'Size' => number_format($this->importFile->getSize() / 1024, 2) . ' KB',
            'Type' => $this->importFile->getMimeType(),
            'Extension' => $this->importFile->getClientOriginalExtension(),
            'Uploaded' => $this->importFile->getMTime() ? date('Y-m-d H:i:s', $this->importFile->getMTime()) : 'Unknown'
        ];
        
        $message = "📁 File Information:\n";
        foreach ($info as $key => $value) {
            $message .= "• {$key}: {$value}\n";
        }
        
        $this->dispatch('notify', type: 'info', message: $message);
    }

    public function render()
    {
        $user = auth()->user();
        
        // Determine user access level and warehouse restrictions
        $warehouseIds = [];
        if ($user->warehouse_id) {
            $warehouseIds = [$user->warehouse_id];
        } elseif ($user->branch_id) {
            $warehouseIds = Warehouse::whereHas('branches', function($q) use ($user) {
                $q->where('branches.id', $user->branch_id);
            })->pluck('id')->toArray();
        }
        
        $query = Item::query()
            ->with(['category', 'stocks.warehouse', 'purchaseItems', 'saleItems'])
            ->where('is_active', true)
            ->when($this->search, function ($query) {
                // Handle SKU search with and without prefix
                $search = $this->search;
                $prefix = config('app.sku_prefix', 'CODE-');
                $rawSearch = str_starts_with($search, $prefix) ? substr($search, strlen($prefix)) : $search;
                
                $query->where(function ($q) use ($search, $rawSearch) {
                    $q->where('name', 'like', '%' . $search . '%')
                        ->orWhere('sku', 'like', '%' . $search . '%')
                        ->orWhere('sku', 'like', '%' . $rawSearch . '%')
                        ->orWhere('barcode', 'like', '%' . $search . '%')
                        ->orWhere('brand', 'like', '%' . $search . '%')
                        ->orWhereHas('category', function ($q) use ($search) {
                            $q->where('name', 'like', '%' . $search . '%');
                        });
                });
            })
            ->when($this->categoryFilter, function ($query) {
                $query->where('category_id', $this->categoryFilter);
            })
            ->when($this->branchFilter && auth()->user()->canAccessLocationFilters(), function ($query) {
                $query->whereHas('stocks.warehouse.branches', function ($q) {
                    $q->where('branches.id', $this->branchFilter);
                });
            })
            ->when($this->warehouseFilter && auth()->user()->canAccessLocationFilters(), function ($query) {
                $query->whereHas('stocks', function ($q) {
                    $q->where('warehouse_id', $this->warehouseFilter);
                });
            })
            ->when($this->hideZeroStock, function ($query) use ($warehouseIds) {
                // Hide items with zero purchase quantity
                $query->whereExists(function ($q) {
                    $q->selectRaw('1')
                      ->from('purchase_items')
                      ->whereColumn('purchase_items.item_id', 'items.id')
                      ->whereNull('purchase_items.deleted_at')
                      ->havingRaw('SUM(purchase_items.quantity) > 0');
                });
            })
            // Filter items based on user's role and warehouse access
            ->when($this->stockFilter, function ($query) use ($warehouseIds) {
                if ($this->stockFilter === 'low') {
                    // Low purchase quantity (below reorder level)
                    $query->whereRaw('(SELECT SUM(quantity) FROM purchase_items WHERE item_id = items.id) <= items.reorder_level')
                          ->whereRaw('(SELECT SUM(quantity) FROM purchase_items WHERE item_id = items.id) > 0');
                } elseif ($this->stockFilter === 'out') {
                    // No purchases
                    $query->whereRaw('(SELECT SUM(quantity) FROM purchase_items WHERE item_id = items.id) = 0 OR (SELECT SUM(quantity) FROM purchase_items WHERE item_id = items.id) IS NULL');
                } elseif ($this->stockFilter === 'in') {
                    // Has purchases
                    $query->whereRaw('(SELECT SUM(quantity) FROM purchase_items WHERE item_id = items.id) > 0');
                }
            })
            ->when($this->sortField === 'stock', function ($query) use ($warehouseIds) {
                // Sort by total purchase quantity instead of stock
                $query->leftJoin('purchase_items', 'items.id', '=', 'purchase_items.item_id')
                      ->orderByRaw('COALESCE(SUM(purchase_items.quantity), 0) ' . $this->sortDirection);
            }, function ($query) {
                if ($this->sortField === 'selling_price') {
                    // Sort by total purchase amount instead of selling price
                    $query->leftJoin('purchase_items as pi_sort', 'items.id', '=', 'pi_sort.item_id')
                          ->orderByRaw('COALESCE(SUM(pi_sort.subtotal), 0) ' . $this->sortDirection);
                } else {
                    $query->orderBy($this->sortField, $this->sortDirection);
                }
            });

        // Group by items.id when using joins to avoid duplicates
        if ($this->sortField === 'stock' || $this->sortField === 'selling_price') {
            $query->groupBy('items.id');
        }
        
        $items = $query->paginate($this->perPage);
        $categories = Category::orderBy('name')->get();
        $branches = Branch::orderBy('name')->get();
        $warehouses = Warehouse::orderBy('name')->get();

        // Calculate low purchase and no purchase counts
        $lowStockCount = Item::whereRaw('(SELECT SUM(quantity) FROM purchase_items WHERE item_id = items.id) <= items.reorder_level')
            ->whereRaw('(SELECT SUM(quantity) FROM purchase_items WHERE item_id = items.id) > 0')
            ->count();
            
        $outOfStockCount = Item::whereRaw('(SELECT SUM(quantity) FROM purchase_items WHERE item_id = items.id) = 0 OR (SELECT SUM(quantity) FROM purchase_items WHERE item_id = items.id) IS NULL')
            ->count();

        $totalCount = Item::count();
        $inStockCount = Item::whereRaw('(SELECT SUM(quantity) FROM purchase_items WHERE item_id = items.id) > 0')->count();

        return view('livewire.items.index', [
            'items' => $items,
            'categories' => $categories,
            'branches' => $branches,
            'warehouses' => $warehouses,
            'lowStockCount' => $lowStockCount,
            'outOfStockCount' => $outOfStockCount,
            'totalCount' => $totalCount,
        ])->title('Inventory Items');
    }

    public function forceCloseModals()
    {
        try {
            \Log::info('Force closing modals');
            
            // Dispatch JavaScript to close modals
            $this->dispatch('force-close-modals');
            
            $this->dispatch('notify', type: 'success', message: 'Modals cleared successfully.');
            
        } catch (\Exception $e) {
            \Log::error('Error force closing modals', ['error' => $e->getMessage()]);
            $this->dispatch('notify', type: 'error', message: 'Error clearing modals: ' . $e->getMessage());
        }
    }

    public function redirectToImport()
    {
        return redirect()->route('admin.items.import');
    }

    public function clearFilters()
    {
        $filtersToReset = ['search', 'categoryFilter', 'stockFilter', 'hideZeroStock'];
        
        // Only reset branch and warehouse filters for users with permission
        if (auth()->user()->canAccessLocationFilters()) {
            $filtersToReset[] = 'branchFilter';
            $filtersToReset[] = 'warehouseFilter';
        }
        
        $this->reset($filtersToReset);
    }

    /**
     * Get total stock for an item based on user's access level
     */
    public function getItemStock($item)
    {
        $user = auth()->user();
        
        // If user has specific warehouse access
        if ($user->warehouse_id) {
            $stock = $item->stocks->where('warehouse_id', $user->warehouse_id)->first();
            return $stock ? $stock->quantity : 0;
        }
        
        // If user has branch access
        if ($user->branch_id) {
            $warehouseIds = Warehouse::whereHas('branches', function($q) use ($user) {
                $q->where('branches.id', $user->branch_id);
            })->pluck('id')->toArray();
            
            return $item->stocks->whereIn('warehouse_id', $warehouseIds)->sum('quantity');
        }
        
        // For super admin - show total stock across all warehouses
        return $item->stocks->sum('quantity');
    }

    /**
     * Get stock status text for an item
     */
    public function getStockStatusText($item)
    {
        $totalStock = $this->getItemStock($item);
        
        if ($totalStock <= 0) {
            return ['text' => 'Out of Stock', 'class' => 'text-danger fw-medium'];
        } elseif ($totalStock <= $item->reorder_level) {
            return ['text' => 'Low Stock', 'class' => 'text-warning fw-medium'];
        } else {
            return ['text' => 'In Stock', 'class' => 'text-success fw-medium'];
        }
    }
}
