<?php

namespace App\Livewire\Items;

use App\Models\Category;
use App\Models\Item;
use App\Enums\ItemUnit;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

#[Layout('components.layouts.app')]
class Create extends Component
{
    public $form = [
        'name' => '',
        'category_id' => '',
        'description' => '',
        'unit' => 'piece',
        'unit_quantity' => 1,
        'item_unit' => '',
    ];

    // Enhanced category search functionality
    public $categorySearch = '';
    public $selectedCategory = null;
    public $selectedCategoryText = '';
    public $filteredCategories = [];
    public $useSearchableCategory = true;

    public $isSubmitting = false;

    public $fromPurchase = false;

    public function mount()
    {
        // Check if this is being called from purchase flow
        $this->fromPurchase = request()->query('from') === 'purchase';
        
        // Check if category query parameter exists and is valid
        $categoryId = request()->query('category');
        if ($categoryId && Category::where('id', $categoryId)->where('is_active', true)->exists()) {
            $this->form['category_id'] = $categoryId;
            $this->loadSelectedCategory();
        }
    }

    /**
     * Load the selected category details when a category_id is set
     */
    public function loadSelectedCategory()
    {
        if (!empty($this->form['category_id'])) {
            $this->selectedCategory = Category::find($this->form['category_id']);
            if ($this->selectedCategory) {
                $this->categorySearch = $this->selectedCategory->name;
                $this->selectedCategoryText = $this->selectedCategory->name;
            }
        } else {
            $this->selectedCategory = null;
            $this->categorySearch = '';
            $this->selectedCategoryText = '';
        }
    }

    /**
     * Search categories (called from Alpine.js)
     */
    public function searchCategories($searchTerm = null)
    {
        if ($searchTerm === null) {
            $searchTerm = $this->categorySearch;
        } else {
            $this->categorySearch = $searchTerm;
        }
        
        if (strlen($searchTerm) >= 1) {
            $this->filteredCategories = $this->performCategorySearch($searchTerm);
        } else {
            $this->filteredCategories = collect();
        }
    }

    /**
     * Perform category search by name
     */
    protected function performCategorySearch($searchTerm)
    {
        return Category::where('name', 'like', '%' . $searchTerm . '%')
            ->where('is_active', true)
            ->orderBy('name')
            ->limit(15)
            ->get()
            ->map(function ($category) {
                // Get the full hierarchy path for this category
                $path = $this->getCategoryPath($category);
                $category->display_path = $path;
                return $category;
            });
    }

    /**
     * Get the full category path including parents
     */
    protected function getCategoryPath($category)
    {
        $path = [$category->name];
        $current = $category;
        
        while ($current->parent_id) {
            $parent = Category::find($current->parent_id);
            if ($parent) {
                array_unshift($path, $parent->name);
                $current = $parent;
            } else {
                break;
            }
        }
        
        return implode(' > ', $path);
    }

    /**
     * Select a category from the dropdown
     */
    public function selectCategory($categoryId)
    {
        $this->form['category_id'] = $categoryId;
        $this->loadSelectedCategory();
        $this->resetValidation('form.category_id');
    }

    /**
     * Clear the selected category
     */
    public function clearCategory()
    {
        $this->form['category_id'] = '';
        $this->selectedCategory = null;
        $this->categorySearch = '';
        $this->selectedCategoryText = '';
        $this->filteredCategories = collect();
    }

    /**
     * Create a new category on the fly
     */
    public function createNewCategory($categoryName)
    {
        try {
            $category = Category::create([
                'name' => trim($categoryName),
                'is_active' => true,
                'created_by' => auth()->id(),
            ]);
            
            $this->selectCategory($category->id);
            
            $this->dispatch('toast', [
                'type' => 'success',
                'message' => "Category '{$categoryName}' created successfully!",
            ]);
            
        } catch (\Exception $e) {
            $this->dispatch('toast', [
                'type' => 'error',
                'message' => 'Failed to create category. Please try again.',
            ]);
        }
    }

    /**
     * Generate unique SKU (without prefix - prefix will be added automatically by model)
     */
    private function generateUniqueSku()
    {
        $maxAttempts = 10;
        $attempts = 0;
        
        do {
            // Generate SKU without prefix (prefix will be added by model accessor)
            $randomString = strtoupper(Str::random(6));
            $sku = $randomString;
            
            $exists = Item::where('sku', $sku)->exists();
            $attempts++;
            
            if (!$exists) {
                return $sku;
            }
            
        } while ($attempts < $maxAttempts);
        
        // If we've reached max attempts, add timestamp to ensure uniqueness
        $timestamp = now()->format('His');
        return strtoupper(Str::random(4)) . $timestamp;
    }

    /**
     * Generate unique barcode
     */
    private function generateUniqueBarcode()
    {
        $maxAttempts = 10;
        $attempts = 0;
        
        do {
            $barcode = date('Ymd') . strtoupper(Str::random(4));
            
            $exists = Item::where('barcode', $barcode)->exists();
            $attempts++;
            
            if (!$exists) {
                return $barcode;
            }
            
        } while ($attempts < $maxAttempts);
        
        // If we've reached max attempts, add timestamp to ensure uniqueness
        $timestamp = now()->format('His');
        return date('Ymd') . strtoupper(Str::random(2)) . $timestamp;
    }

    // Real-time validation
    public function updated($propertyName)
    {
        // Format item name on change and validate uniqueness
        if ($propertyName === 'form.name' && !empty($this->form['name'])) {
            $this->form['name'] = ucwords(strtolower($this->form['name']));
            
            // Real-time duplicate check
            if (strlen($this->form['name']) >= 2) {
                $exists = Item::where('name', $this->form['name'])->exists();
                if ($exists) {
                    $this->addError('form.name', 'This item name already exists. Please choose a different name.');
                } else {
                    $this->resetErrorBag('form.name');
                }
            }
        }
        
        // Ensure unit quantity is at least 1
        if ($propertyName === 'form.unit_quantity') {
            if ((int)$this->form['unit_quantity'] < 1) {
                $this->form['unit_quantity'] = 1;
            }
        }
    }



    public function save()
    {
        // Check permissions
        if (!Auth::user()->can('create', Item::class)) {
            session()->flash('message', 'You are not authorized to create items.');
            return;
        }

        $this->isSubmitting = true;
        
        // Debug: Log the form data
        Log::info('Item creation attempt', [
            'form_data' => $this->form,
            'user_id' => Auth::id(),
        ]);

        try {
            $validated = $this->validate([
                'form.name' => 'required|string|max:255|min:2|unique:items,name',
                'form.category_id' => 'required|exists:categories,id',
                'form.description' => 'nullable|string|max:1000',
                'form.unit' => 'required|string|in:piece',
                'form.unit_quantity' => 'required|integer|min:1|max:99999',
                'form.item_unit' => 'required|string|in:' . implode(',', ItemUnit::values()),
            ], [
                'form.name.required' => 'Item name is required.',
                'form.name.min' => 'Item name must be at least 2 characters.',
                'form.name.max' => 'Item name cannot exceed 255 characters.',
                'form.name.unique' => 'This item name already exists. Please choose a different name.',
                'form.category_id.required' => 'Please select a category.',
                'form.category_id.exists' => 'Selected category is invalid.',
                'form.unit_quantity.required' => 'Items per piece is required.',
                'form.unit_quantity.min' => 'Items per piece must be at least 1.',
                'form.unit_quantity.max' => 'Items per piece cannot exceed 99,999.',
                'form.item_unit.required' => 'Please select an item unit.',
                'form.item_unit.in' => 'Selected item unit is invalid.',
                'form.description.max' => 'Description cannot exceed 1000 characters.',
            ]);

            Log::info('Validation passed', ['validated' => $validated]);

            DB::beginTransaction();

            // Generate unique identifiers
            $sku = $this->generateUniqueSku();
            $barcode = $this->generateUniqueBarcode();

            Log::info('Generated identifiers', ['sku' => $sku, 'barcode' => $barcode]);

            // Set default pricing values
            $costPrice = 0;
            $sellingPrice = 0;
            
            // Add auto-generated fields and handle null values
            $itemData = array_merge($validated['form'], [
                'cost_price' => $costPrice,
                'selling_price' => $sellingPrice,
                'sku' => $sku,
                'barcode' => $barcode,
                'is_active' => true,
                'created_by' => Auth::id(),
                'reorder_level' => 1, // Default value
                'branch_id' => Auth::user()->isSuperAdmin() ? null : Auth::user()->branch_id,
            ]);

            Log::info('Item data prepared', ['itemData' => $itemData]);

            // Create the item
            $item = Item::create($itemData);

            Log::info('Item created successfully', ['item_id' => $item->id]);

            DB::commit();

            session()->flash('success', 'Item created successfully!');

            // Redirect back to purchase if called from purchase flow
            if ($this->fromPurchase) {
                return $this->redirect(route('admin.purchases.create'), navigate: true);
            }

            return $this->redirect(route('admin.items.index'));

        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->isSubmitting = false;
            Log::error('Validation failed', ['errors' => $e->errors()]);
            
            // Check for duplicate name error and provide helpful message
            if (isset($e->errors()['form.name']) && str_contains($e->errors()['form.name'][0], 'already been taken')) {
                session()->flash('error', 'An item with this name already exists. Please choose a different name.');
            }
            
            throw $e;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->isSubmitting = false;
            
            session()->flash('message', 'An error occurred while creating the item. Please try again.');

            // Log the error for troubleshooting
            Log::error('Item creation failed: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'form_data' => $this->form,
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    public function resetForm()
    {
        $this->form = [
            'name' => '',
            'category_id' => '',
            'description' => '',
            'unit' => 'piece',
            'unit_quantity' => 1,
            'item_unit' => '',
        ];
        
        $this->selectedCategory = null;
        $this->categorySearch = '';
        $this->selectedCategoryText = '';
        $this->filteredCategories = collect();
        $this->resetValidation();
    }

    public function cancel()
    {
        // Return to purchase if called from purchase flow
        if ($this->fromPurchase) {
            return $this->redirect(route('admin.purchases.create'), navigate: true);
        }
        
        return $this->redirect(route('admin.items.index'));
    }
    


    public function render()
    {
        // The full categories list is only used for the initial load
        // and when no search is performed
        $categories = $this->getHierarchicalCategories();
        $itemUnits = collect(ItemUnit::cases())->mapWithKeys(function ($unit) {
            return [$unit->value => $unit->label()];
        });
        
        return view('livewire.items.create', [
            'categories' => $categories,
            'itemUnits' => $itemUnits,
            'currencySymbol' => 'ETB',
        ])->title('Create Item');
    }

    /**
     * Get categories in hierarchical order for display
     */
    private function getHierarchicalCategories()
    {
        $categories = collect();
        $rootCategories = Category::where('parent_id', null)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        foreach ($rootCategories as $category) {
            $this->addCategoryWithChildren($categories, $category, 0);
        }

        return $categories;
    }

    /**
     * Recursively add category and its children to the collection
     */
    private function addCategoryWithChildren($collection, $category, $level)
    {
        $category->display_name = str_repeat('— ', $level) . $category->name;
        $collection->push($category);

        $children = Category::where('parent_id', $category->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        foreach ($children as $child) {
            $this->addCategoryWithChildren($collection, $child, $level + 1);
        }
    }
}
