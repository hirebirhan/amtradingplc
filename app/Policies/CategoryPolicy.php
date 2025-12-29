<?php

namespace App\Policies;

use App\Models\Category;
use App\Models\User;

class CategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('categories.view');
    }

    public function view(User $user, Category $category): bool
    {
        return $user->can('categories.view') && $user->canAccessBranch($category->branch_id);
    }

    public function create(User $user): bool
    {
        return $user->can('categories.create');
    }

    public function update(User $user, Category $category): bool
    {
        return $user->can('categories.edit') && $user->canAccessBranch($category->branch_id);
    }

    public function delete(User $user, Category $category): bool
    {
        return $user->can('categories.delete') && 
               $user->canAccessBranch($category->branch_id) &&
               !$category->items()->exists();
    }

    public function restore(User $user, Category $category): bool
    {
        return $user->can('categories.create') && $user->canAccessBranch($category->branch_id);
    }
}