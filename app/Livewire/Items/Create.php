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

#[Layout('components.layouts.app')]
class Create extends Component
{
    public $form = [
        'name' => '',
        'category_id' => '',
        'description' => '',
        'cost_price_per_unit' => '',
        'selling_price_per_unit' => '',
        'unit' => 'piece',
        'unit_quantity' => 1,
        'item_unit' => '',
        'reorder_level' => null,
    ];

    // Enhanced category search functionality
    public $categorySearch = '';
    public $selectedCategory = null;
    public $selectedCategoryText = '';
    public $filteredCategories = [];
    public $useSearchableCategory = true;

    public $isSubmitting = false;

    public function mount()
    {
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

    // Real-time validation for cost and selling price
    public function updated($propertyName)
    {
        // Format item name on change
        if ($propertyName === 'form.name' && !empty($this->form['name'])) {
            $this->form['name'] = ucwords(strtolower($this->form['name']));
        }
        
        // Ensure unit quantity is at least 1
        if ($propertyName === 'form.unit_quantity') {
            if ((int)$this->form['unit_quantity'] < 1) {
                $this->form['unit_quantity'] = 1;
            }
        }

        // Validate selling price vs cost price
        if (in_array($propertyName, ['form.cost_price', 'form.selling_price'])) {
            $this->validateSellingPrice();
        }
    }

    /**
     * Validate that selling price is higher than cost price
     */
    protected function validateSellingPrice()
    {
        if (!empty($this->form['cost_price']) && !empty($this->form['selling_price'])) {
            if ((float)$this->form['selling_price'] <= (float)$this->form['cost_price']) {
                $this->addError('form.selling_price', 'Selling price must be higher than cost price');
            } else {
                $this->resetErrorBag('form.selling_price');
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
        \Log::info('Item creation attempt', [
            'form_data' => $this->form,
            'user_id' => Auth::id(),
        ]);

        try {
            $validated = $this->validate([
                'form.name' => 'required|string|max:255|min:2',
                'form.category_id' => 'required|exists:categories,id',
                'form.description' => 'nullable|string|max:1000',
                'form.cost_price_per_unit' => 'required|numeric|min:0|max:999999.99',
                'form.selling_price_per_unit' => 'required|numeric|min:0|max:999999.99',
                'form.unit' => 'required|string|in:piece',
                'form.unit_quantity' => 'required|integer|min:1|max:99999',
                'form.item_unit' => 'required|string|in:' . implode(',', ItemUnit::values()),
                'form.reorder_level' => 'nullable|integer|min:0|max:99999',
            ]);

            \Log::info('Validation passed', ['validated' => $validated]);

            // Additional validation to ensure selling price is higher than cost price
            if ((float)$validated['form']['selling_price_per_unit'] <= (float)$validated['form']['cost_price_per_unit']) {
                throw ValidationException::withMessages([
                    'form.selling_price_per_unit' => ['Selling price per unit must be higher than cost price per unit'],
                ]);
            }

            DB::beginTransaction();

            // Generate unique identifiers
            $sku = $this->generateUniqueSku();
            $barcode = $this->generateUniqueBarcode();

            \Log::info('Generated identifiers', ['sku' => $sku, 'barcode' => $barcode]);

            // Calculate total prices for backward compatibility with proper type casting
            $costPrice = (float)$validated['form']['cost_price_per_unit'] * (int)$validated['form']['unit_quantity'];
            $sellingPrice = (float)$validated['form']['selling_price_per_unit'] * (int)$validated['form']['unit_quantity'];
            
            // Add auto-generated fields and handle null values
            $itemData = array_merge($validated['form'], [
                'cost_price' => $costPrice,
                'selling_price' => $sellingPrice,
                'sku' => $sku,
                'barcode' => $barcode,
                'is_active' => true,
                'created_by' => Auth::id(),
                'reorder_level' => $validated['form']['reorder_level'] ?? 1, // Default to 1 if null
            ]);

            \Log::info('Item data prepared', ['itemData' => $itemData]);

            // Create the item
            $item = Item::create($itemData);

            \Log::info('Item created successfully', ['item_id' => $item->id]);

            DB::commit();

            session()->flash('message', 'Item created successfully!');

            return $this->redirect(route('admin.items.index'));

        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->isSubmitting = false;
            \Log::error('Validation failed', ['errors' => $e->errors()]);
            throw $e;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->isSubmitting = false;
            
            session()->flash('message', 'An error occurred while creating the item. Please try again.');

            // Log the error for troubleshooting
            \Log::error('Item creation failed: ' . $e->getMessage(), [
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
            'cost_price_per_unit' => '',
            'selling_price_per_unit' => '',
            'unit' => 'piece',
            'unit_quantity' => 1,
            'item_unit' => '',
            'reorder_level' => null,
        ];
        
        $this->selectedCategory = null;
        $this->categorySearch = '';
        $this->selectedCategoryText = '';
        $this->filteredCategories = collect();
        $this->resetValidation();
    }

    public function cancel()
    {
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
