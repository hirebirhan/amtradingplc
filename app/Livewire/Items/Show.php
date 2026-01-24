<?php

namespace App\Livewire\Items;

use App\Models\Item;
use App\Models\Stock;
use App\Models\StockHistory;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('components.layouts.app')]
class Show extends Component
{
    public $itemId;
    public Item $item;

    public function mount($itemId)
    {
        $this->itemId = $itemId;
        $this->item = Item::findOrFail($itemId);
    }

    public function delete($itemId)
    {
        $item = Item::findOrFail($itemId);
        
        // Check permission
        if (!auth()->user()->can('delete', $item)) {
            $this->dispatch('notify', ['type' => 'error', 'message' => 'You are not authorized to delete this item.']);
            return;
        }

        // Check if the item has stock history
        if ($item->stockHistories()->count() > 0) {
            $this->dispatch('notify', ['type' => 'error', 'message' => 'Cannot delete item with stock history records.']);
            return;
        }

        // Check if the item has stock
        if ($item->stocks()->sum('quantity') > 0) {
            $this->dispatch('notify', ['type' => 'error', 'message' => 'Cannot delete item with existing stock. Please adjust stock to zero first.']);
            return;
        }

        // Delete the item
        $itemName = $item->name;
        $item->delete();
        
        $this->dispatch('notify', ['type' => 'success', 'message' => "Item '{$itemName}' deleted successfully!"]);
        
        // Redirect to items index
        return $this->redirect(route('admin.items.index'));
    }

    public function render()
    {
        $user = auth()->user();
        $branchId = null;
        
        // Determine branch context for financial data
        if (!$user->isSuperAdmin() && !$user->isGeneralManager()) {
            $branchId = $user->branch_id;
        }
        
        // Get stock levels for this item across all warehouses
        $stocks = Stock::with('warehouse')
            ->where('item_id', $this->item->id)
            ->orderBy('quantity', 'desc')
            ->get();

        // Get recent stock histories for this item
        $stockHistories = StockHistory::with('warehouse', 'user')
            ->where('item_id', $this->item->id)
            ->latest()
            ->take(10)
            ->get();

        // Calculate profit margin
        $margin = $this->item->selling_price > 0 && $this->item->cost_price > 0 ?
            (($this->item->selling_price - $this->item->cost_price) / $this->item->selling_price) * 100 : 0;

        // Get recent activities (using stock histories as activities)
        $recentActivities = $stockHistories->take(5);
        
        // Calculate branch-specific financial data
        $totalPurchaseAmount = $this->item->getTotalPurchaseAmount($branchId);
        $totalSalesAmount = $this->item->getTotalSalesAmount($branchId);
        $costPerPiece = $this->item->getCostPerPiece($branchId);

        return view('livewire.items.show', [
            'stocks' => $stocks,
            'stockHistories' => $stockHistories,
            'recentActivities' => $recentActivities,
            'totalStock' => $this->item->getTotalStockAttribute(),
            'isLowStock' => $this->item->isLowStock(),
            'margin' => $margin,
            'totalPurchaseAmount' => $totalPurchaseAmount,
            'totalSalesAmount' => $totalSalesAmount,
            'costPerPiece' => $costPerPiece,
        ])->title('Item Details - ' . $this->item->name);
    }
}
