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
            // For Items and Categories, allow NULL branch_id (Global) if created by SuperAdmin/GM
            if (($model instanceof \App\Models\Item || $model instanceof \App\Models\Category) && 
                (auth()->user()?->isSuperAdmin() || auth()->user()?->isGeneralManager())) {
                // Do not force branch_id if it's not set, allowing it to remain NULL (Global)
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
        // Allow Global Items and Categories (branch_id is null) to be visible
        if ($model instanceof \App\Models\Item || $model instanceof \App\Models\Category) {
            return $query->where(function($q) use ($branchId) {
                $q->where('branch_id', $branchId)
                  ->orWhereNull('branch_id');
            });
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