<?php

namespace App\Services;

use App\Models\Item;
use App\Models\Category;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Exception;

class BranchItemService
{
    public function deleteItem(Item $item, User $user): bool
    {
        if (!$user->canAccessBranch($item->branch_id)) {
            throw new Exception('Cannot delete items from this branch');
        }

        return DB::transaction(function () use ($item) {
            if ($item->stocks()->where('piece_count', '>', 0)->exists() || 
                $item->saleItems()->exists() || 
                $item->purchaseItems()->exists()) {
                return $item->update(['is_active' => false]);
            }
            return $item->delete();
        });
    }

    public function deleteCategory(Category $category, User $user): bool
    {
        if (!$user->canAccessBranch($category->branch_id)) {
            throw new Exception('Cannot delete categories from this branch');
        }

        return DB::transaction(function () use ($category) {
            if ($category->items()->exists()) {
                throw new Exception('Cannot delete category with existing items');
            }
            return $category->delete();
        });
    }

    public function restoreItem(int $itemId, User $user): Item
    {
        $item = Item::withTrashed()->findOrFail($itemId);
        if (!$user->canAccessBranch($item->branch_id)) {
            throw new Exception('Cannot restore items in this branch');
        }
        $item->restore();
        $item->update(['is_active' => true]);
        return $item;
    }

    public function restoreCategory(int $categoryId, User $user): Category
    {
        $category = Category::withTrashed()->findOrFail($categoryId);
        if (!$user->canAccessBranch($category->branch_id)) {
            throw new Exception('Cannot restore categories in this branch');
        }
        $category->restore();
        $category->update(['is_active' => true]);
        return $category;
    }

    public function generateBranchSku(int $branchId): string
    {
        $prefix = 'BR' . str_pad($branchId, 2, '0', STR_PAD_LEFT);
        $count = Item::forBranch($branchId)->withTrashed()->count() + 1;
        return $prefix . '-' . str_pad($count, 6, '0', STR_PAD_LEFT);
    }
}