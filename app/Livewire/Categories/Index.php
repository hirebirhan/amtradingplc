<?php

namespace App\Livewire\Categories;

use App\Models\Category;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Exports\CategoriesExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

#[Layout('components.layouts.app')]
class Index extends Component
{
    use WithPagination, AuthorizesRequests;

    public $search = '';
    public $perPage = 10;
    public $sortField = 'name';
    public $sortDirection = 'asc';

    public $parentFilter = '';
    public $modalCategoryId = null;

    protected $listeners = [
        'delete' => 'delete',
        'validateDelete' => 'validateDelete'
    ];

    protected $queryString = [
        'search' => ['except' => ''],
        'parentFilter' => ['except' => ''],
        'perPage' => ['except' => 10],
        'sortField' => ['except' => 'name'],
        'sortDirection' => ['except' => 'asc'],
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingParentFilter()
    {
        $this->resetPage();
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
     * Validate a category before deletion to check for dependencies
     */
    public function validateDelete($id)
    {
        $category = Category::withCount(['items', 'children'])->findOrFail($id);
        
        $this->dispatch('showDeleteWarnings', [
            'categoryName' => $category->name,
            'hasItems' => $category->items_count > 0 ? $category->items_count : false,
            'hasChildren' => $category->children_count > 0 ? $category->children_count : false
        ]);
    }

    /**
     * Delete a category and handle related data appropriately
     */
    public function delete($id)
    {
        Log::debug('Livewire delete called', ['id' => $id]);
        // Check permission
        if (!auth()->user()->can('categories.delete')) {
            session()->flash('error', 'You do not have permission to delete categories.');
            $this->dispatch('notify', type: 'error', message: 'You do not have permission to delete categories.');
            return;
        }

        $category = Category::withCount(['items', 'children'])->findOrFail($id);

        // Integrity checks
        if ($category->items_count > 0) {
            session()->flash('error', 'Cannot delete category with items. Please move or delete items first.');
            $this->dispatch('notify', type: 'error', message: 'Cannot delete category with items. Please move or delete items first.');
            return;
        }
        if ($category->children_count > 0) {
            session()->flash('error', 'Cannot delete category with subcategories. Please remove subcategories first.');
            $this->dispatch('notify', type: 'error', message: 'Cannot delete category with subcategories. Please remove subcategories first.');
            return;
        }

        try {
            DB::beginTransaction();
            $category->delete();
            DB::commit();
            session()->flash('success', "Category '{$category->name}' deleted successfully.");
            $this->dispatch('notify', type: 'success', message: "Category '{$category->name}' deleted successfully.");
            $this->dispatch('categoryDeleted');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Category deletion failed', ['error' => $e->getMessage()]);
            session()->flash('error', "Error deleting category: {$e->getMessage()}");
            $this->dispatch('notify', type: 'error', message: "Error deleting category: {$e->getMessage()}");
        }
    }

    /**
     * Export categories to CSV/Excel
     */
    public function exportCategories($type = 'csv')
    {
        if (!auth()->user()->can('categories.view')) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'You do not have permission to export categories.'
            ]);
            return;
        }

        $filename = 'categories-' . now()->format('Y-m-d');

        if ($type === 'excel') {
            return Excel::download(
                new CategoriesExport($this->getFilteredCategories(false)),
                $filename . '.xlsx'
            );
        }

        // CSV export
        return response()->streamDownload(
            fn() => $this->generateCategoriesExport(),
            $filename . '.csv'
        );
    }

    /**
     * Generate CSV data for export
     */
    private function generateCategoriesExport()
    {
        $categories = $this->getFilteredCategories(false)->get();
        $output = fopen('php://output', 'w');
        
        // Add BOM for Excel UTF-8 compatibility
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Headers
        fputcsv($output, [
            'ID',
            'Name',
            'Description',
            'Parent Category',
            'Items Count',
            'Subcategories Count',
            'Status',
            'Created At'
        ]);
        
        foreach ($categories as $category) {
            fputcsv($output, [
                $category->id,
                $category->name,
                $category->description ?: 'N/A',
                $category->parent ? $category->parent->name : 'None',
                $category->items_count,
                $category->children_count,
                $category->is_active ? 'Active' : 'Inactive',
                $category->created_at->format('Y-m-d H:i:s')
            ]);
        }
        
        fclose($output);
    }

    /**
     * Get filtered categories query
     */
    private function getFilteredCategories($paginate = true)
    {
        $query = Category::query()
            ->withCount(['items', 'children'])
            ->with('parent')
            ->when($this->search, function (Builder $query) {
                $query->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('description', 'like', '%' . $this->search . '%');
            })
            ->when($this->parentFilter === 'parent', function (Builder $query) {
                $query->whereNull('parent_id');
            })
            ->when($this->parentFilter === 'child', function (Builder $query) {
                $query->whereNotNull('parent_id');
            })
            ->where('is_active', true)
            ->orderBy($this->sortField, $this->sortDirection);
            
        return $paginate 
            ? $query->paginate($this->perPage) 
            : $query;
    }

    public function clearFilters()
    {
        $this->reset(['search', 'parentFilter']);
    }

    public function render()
    {
        // Get total category count
        $totalCategories = Category::count();
        
        // Get total category count (all active)
        $totalCategoriesCount = Category::where('is_active', true)->count();
        
        // Get total items in all categories
        $totalItems = Category::withCount('items')->get()->sum('items_count');
        
        return view('livewire.categories.index', [
            'categories' => $this->getFilteredCategories(),
            'totalCategories' => $totalCategoriesCount,
            'totalItems' => $totalItems
        ])->title('Categories');
    }
}
