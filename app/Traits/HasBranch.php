<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Branch;

trait HasBranch
{
    protected static function bootHasBranch()
    {
        static::creating(function ($model) {
            // Items and Categories are now global - always set branch_id to NULL
            if ($model instanceof \App\Models\Item || $model instanceof \App\Models\Category) {
                $model->branch_id = null;
                return;
            }

            if (!$model->branch_id && auth()->user()?->branch_id) {
                $model->branch_id = auth()->user()->branch_id;
            }
        });
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function scopeForBranch(Builder $query, int $branchId): Builder
    {
        $model = $query->getModel();
        // Items and Categories are global - no branch filtering
        if ($model instanceof \App\Models\Item || $model instanceof \App\Models\Category) {
            return $query; // Return all items/categories
        }
        
        return $query->where('branch_id', $branchId);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function belongsToBranch(int $branchId): bool
    {
        return $this->branch_id === $branchId;
    }
}