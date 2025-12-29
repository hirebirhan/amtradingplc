<?php

namespace App\Livewire\Categories;

use Livewire\Component;
use App\Models\Category;
use App\Services\BranchItemService;
use App\Traits\HasFlashMessages;

class CategoryManager extends Component
{
    use HasFlashMessages;

    public $branch;
    public $categories;
    public $showDeleted = false;
    public $name = '';
    public $code = '';
    public $description = '';
    public $editingId = null;

    public function mount()
    {
        $this->branch = auth()->user()->branch ?? null;
        $this->loadCategories();
    }

    public function loadCategories()
    {
        $query = Category::query();
        
        // Apply branch scope for non-admin users
        if (!auth()->user()->isSuperAdmin() && !auth()->user()->isGeneralManager()) {
            $query->forBranch(auth()->user()->branch_id);
        }
        
        if ($this->showDeleted) {
            $query->withTrashed();
        } else {
            $query->active();
        }
        
        $this->categories = $query->orderBy('name')->get();
    }

    public function save()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:15',
        ]);

        $data = [
            'name' => $this->name,
            'code' => strtoupper($this->code),
            'description' => $this->description,
            'branch_id' => auth()->user()->branch_id,
        ];

        if ($this->editingId) {
            Category::findOrFail($this->editingId)->update($data);
            $this->flashSuccess('Category updated successfully');
        } else {
            Category::create($data);
            $this->flashSuccess('Category created successfully');
        }

        $this->reset(['name', 'code', 'description', 'editingId']);
        $this->loadCategories();
    }

    public function edit($categoryId)
    {
        $category = Category::findOrFail($categoryId);
        
        if (!auth()->user()->canAccessBranch($category->branch_id)) {
            session()->flash('error', 'Cannot edit categories from other branches');
            return;
        }

        $this->editingId = $category->id;
        $this->name = $category->name;
        $this->code = $category->code;
        $this->description = $category->description;
    }

    public function delete($categoryId)
    {
        try {
            $category = Category::findOrFail($categoryId);
            $service = new BranchItemService();
            
            if ($service->deleteCategory($category, auth()->user())) {
                $this->flashSuccess('Category deleted successfully');
                $this->loadCategories();
            }
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function restore($categoryId)
    {
        try {
            $service = new BranchItemService();
            $service->restoreCategory($categoryId, auth()->user());
            $this->flashSuccess('Category restored successfully');
            $this->loadCategories();
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.categories.category-manager');
    }
}