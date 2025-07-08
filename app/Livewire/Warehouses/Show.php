<?php

namespace App\Livewire\Warehouses;

use App\Models\Warehouse;
use App\Models\Stock;
use App\Models\Item;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;

#[Layout('layouts.app')]
#[Title('Warehouse Details')]
class Show extends Component
{
    use WithPagination;

    public Warehouse $warehouse;
    public $perPage = 10;
    public $warehouseExists = false;
    protected $paginationTheme = 'bootstrap';

    public function mount($warehouse)
    {
        try {
            // Explicitly find the warehouse by ID to avoid route model binding issues
            if (is_numeric($warehouse)) {
                $warehouseModel = Warehouse::with('branches')->findOrFail($warehouse);
                $this->warehouse = $warehouseModel;
                $this->warehouseExists = true;
                Log::info('Warehouse found', ['id' => $this->warehouse->id, 'name' => $this->warehouse->name]);
            } else {
                $this->warehouse = $warehouse;
                $this->warehouseExists = $warehouse->exists;
                Log::info('Warehouse found', ['id' => $this->warehouse->id, 'name' => $this->warehouse->name]);
            }
        } catch (ModelNotFoundException $e) {
            Log::error('Warehouse not found', ['id' => $warehouse ?? 'not set']);
            $this->redirect(route('admin.warehouses.index'));
        } catch (\Exception $e) {
            Log::error('Error loading warehouse', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            $this->redirect(route('admin.warehouses.index'));
        }
    }

    public function updatedPerPage()
    {
        $this->resetPage();
    }

    public function render()
    {
        if (!$this->warehouseExists) {
            return view('livewire.warehouses.not-found', [
                'active' => 'warehouses',
            ])->title('Warehouse Not Found');
        }

        try {
            // Get stocks with eager loading of item relationships
            $stocks = Stock::where('warehouse_id', $this->warehouse->id)
                ->with(['item' => function($query) {
                    $query->with('category')->withTrashed();
                }])
                ->paginate($this->perPage);
                
            Log::info('Stocks loaded', [
                'count' => $stocks->count(),
                'warehouse_id' => $this->warehouse->id
            ]);

            // Get low stock items
            $lowStockItems = Stock::where('warehouse_id', $this->warehouse->id)
                ->with(['item' => function($query) {
                    $query->with('category')->withTrashed();
                }])
                ->whereHas('item', function($query) {
                    $query->whereColumn('stocks.quantity', '<=', 'items.reorder_level');
                })
                ->where('quantity', '>', 0)
                ->get();
                
            $lowStockCount = $lowStockItems->count();

            // Ensure the warehouse is fresh with branches loaded
            $warehouse = $this->warehouse->fresh()->load(['branches']);
            
            return view('livewire.warehouses.show', [
                'warehouse' => $warehouse,
                'stocks' => $stocks,
                'lowStockItems' => $lowStockItems,
                'lowStockCount' => $lowStockCount,
                'active' => 'warehouses',
            ])->title("Warehouse: {$warehouse->name}");
        } catch (\Exception $e) {
            Log::error('Error rendering warehouse page', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            session()->flash('error', 'There was an error loading the warehouse data.');
            return redirect()->route('admin.warehouses.index');
        }
    }
}