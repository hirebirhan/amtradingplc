<?php

declare(strict_types=1);

namespace App\Livewire\Categories;

use App\Models\Category;
use App\Traits\HasFlashMessages;
use Livewire\Component;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class Form extends Component
{
    use HasFlashMessages;
    public Category $category;
    public bool $isEdit = false;
    public bool $isSubmitting = false;

    public string $name = '';
    public ?string $description = null;
    public ?int $parent_id = null;
    public bool $is_active = true;

    public array $parentCategories = [];

    protected $listeners = ['refresh' => '$refresh'];

    protected function rules()
    {
        $branchId = auth()->user()->branch_id;
        
        $rules = [
            'name' => ['required', 'string', 'min:3', 'max:255', 'regex:/^[a-zA-Z0-9\s\-_]+$/'],
            'description' => 'nullable|string|max:1000',
            'parent_id' => ['nullable', 'exists:categories,id', function ($attribute, $value, $fail) {
                if ($value && $this->isEdit && $value == $this->category->id) {
                    $fail('A category cannot be its own parent.');
                }
            }],
        ];

        // Add unique validation for name within branch
        if ($this->isEdit) {
            $rules['name'][] = "unique:categories,name,{$this->category->id},id,branch_id,{$branchId}";
        } else {
            $rules['name'][] = "unique:categories,name,NULL,id,branch_id,{$branchId}";
        }

        return $rules;
    }

    protected $validationAttributes = [
        'name' => 'category name',
        'parent_id' => 'parent category',
    ];
    
    protected function messages()
    {
        return [
            'name.required' => 'Category name is required.',
            'name.min' => 'Category name must be at least 3 characters.',
            'name.max' => 'Category name cannot exceed 255 characters.',
            'name.unique' => 'This category name already exists. Please use a different name.',
            'name.regex' => 'Category name can only contain letters, numbers, spaces, hyphens, and underscores.',
            'description.max' => 'Description cannot exceed 1000 characters.',
            'parent_id.exists' => 'The selected parent category is invalid.',
        ];
    }

    public function mount(Category $category = null, bool $isEdit = false)
    {
        $this->isEdit = $isEdit;

        if ($isEdit && $category->exists) {
            $this->category = $category;
            $this->name = $category->name;
            $this->description = $category->description;
            $this->parent_id = $category->parent_id;
            $this->is_active = $category->is_active;
        } else {
            $this->category = new Category();
            $this->is_active = true; // Always active for new categories
        }

        $this->loadParentCategories();
    }

    private function loadParentCategories(): void
    {
        try {
            $branchId = auth()->user()->branch_id;
            
            if ($this->isEdit) {
                $excludedIds = $this->getCategoryAndChildrenIds($this->category);
                $this->parentCategories = Category::where('branch_id', $branchId)
                    ->whereNotIn('id', $excludedIds)
                    ->orderBy('name')
                    ->get()
                    ->toArray();
            } else {
                $this->parentCategories = Category::where('branch_id', $branchId)
                    ->orderBy('name')
                    ->get()
                    ->toArray();
            }
        } catch (\Exception $e) {
            Log::error('Failed to load parent categories', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);
            session()->flash('error', 'Failed to load parent categories. Please refresh the page and try again.');
        }
    }

    public function updatedName($value): void
    {
        $this->validateOnly('name');
    }

    public function updatedDescription($value): void
    {
        $this->validateOnly('description');
    }

    public function updatedParentId($value): void
    {
        $this->validateOnly('parent_id');
    }

    public function save(): void
    {
        try {
            $this->isSubmitting = true;
            
            // Debug logging
            Log::info('Category save attempt started', [
                'name' => $this->name,
                'description' => $this->description,
                'parent_id' => $this->parent_id,
                'is_edit' => $this->isEdit,
                'user_id' => auth()->id(),
                'branch_id' => auth()->user()->branch_id
            ]);
            
            $validated = $this->validate();
            
            Log::info('Category validation passed', ['validated_data' => $validated]);

            // Generate a unique code from the name
            $baseCode = 'CAT-' . strtoupper(Str::substr(Str::slug($validated['name']), 0, 8));
            $code = $baseCode;
            $counter = 1;
            
            // If editing and we already have a code, keep it
            if ($this->isEdit && !empty($this->category->code)) {
                $code = $this->category->code;
            } else {
                // Otherwise generate a unique code
                $branchId = auth()->user()->branch_id;
                while (Category::where('code', $code)
                    ->where('branch_id', $branchId)
                    ->when($this->isEdit, function($query) {
                        return $query->where('id', '!=', $this->category->id);
                    })
                    ->exists()) {
                    $suffix = '-' . $counter;
                    // Ensure we don't exceed 15 characters
                    if (strlen($baseCode . $suffix) <= 15) {
                        $code = $baseCode . $suffix;
                    } else {
                        // If we're getting too long, truncate the base code
                        $maxBaseLength = 15 - strlen($suffix);
                        $code = Str::substr($baseCode, 0, $maxBaseLength) . $suffix;
                    }
                    $counter++;
                    
                    // Safety check to prevent infinite loops
                    if ($counter > 999) {
                        throw new \Exception('Unable to generate unique category code after 999 attempts');
                    }
                }
            }
            
            // For new categories, always set active status to true
            // For existing categories, keep the current status
            if (!$this->isEdit) {
                $this->is_active = true;
            }

            // Prepare data for saving
            $categoryData = [
                'name' => trim($validated['name']),
                'code' => $code,
                'description' => $validated['description'] ? trim($validated['description']) : null,
                'parent_id' => $validated['parent_id'],
                'branch_id' => auth()->user()->branch_id,
                'is_active' => $this->is_active,
            ];
            
            // Generate slug if not editing or if name changed
            if (!$this->isEdit || $this->category->name !== $categoryData['name']) {
                $categoryData['slug'] = Str::slug($categoryData['name']);
                
                // Ensure slug is unique within branch
                $baseSlug = $categoryData['slug'];
                $counter = 1;
                while (Category::where('slug', $categoryData['slug'])
                    ->where('branch_id', $categoryData['branch_id'])
                    ->when($this->isEdit, function($query) {
                        return $query->where('id', '!=', $this->category->id);
                    })
                    ->exists()) {
                    $categoryData['slug'] = $baseSlug . '-' . $counter;
                    $counter++;
                }
            }
            
            $this->category->fill($categoryData);
            
            $this->category->save();

            // Log the action
            Log::info('Category saved successfully', [
                'category_name' => $this->category->name,
                'category_code' => $this->category->code,
                'is_edit' => $this->isEdit,
                'created_by' => auth()->id(),
                'category_id' => $this->category->id
            ]);

            $this->dispatch('category-saved', $this->category->id);

            if (!$this->isEdit) {
                $this->reset(['name', 'description', 'parent_id']);
                $this->is_active = true;
                $this->loadParentCategories(); // Reload parent categories
                $this->flashCrudSuccess('category', 'created');
            } else {
                $this->flashCrudSuccess('category', 'updated');
            }

        } catch (\Illuminate\Validation\ValidationException $e) {
            // Validation errors are handled by Livewire automatically
            Log::warning('Category save validation failed', [
                'errors' => $e->errors(),
                'user_id' => auth()->id()
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to save category', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'category_data' => [
                    'name' => $this->name,
                    'is_edit' => $this->isEdit
                ]
            ]);
            
            $this->addError('name', 'Failed to save category. Please try again.');
        } finally {
            $this->isSubmitting = false;
        }
    }

    public function cancel(): void
    {
        if (!empty($this->name) || !empty($this->description) || $this->parent_id !== null) {
            // Show confirmation dialog
            $this->dispatch('show-confirmation', [
                'title' => 'Discard Changes?',
                'message' => 'You have unsaved changes. Are you sure you want to leave?',
                'confirmText' => 'Yes, Discard',
                'cancelText' => 'Stay',
                'action' => 'cancel-category-creation'
            ]);
        } else {
            $this->redirect('/admin/categories', navigate: true);
            return;
        }
    }

    public function confirmCancel(): void
    {
        $this->redirect('/admin/categories', navigate: true);
    }

    protected function getCategoryAndChildrenIds(Category $category)
    {
        $ids = [$category->id];

        foreach ($category->children as $child) {
            $ids = array_merge($ids, $this->getCategoryAndChildrenIds($child));
        }

        return $ids;
    }

    public function render()
    {
        return view('livewire.categories.form');
    }
}
