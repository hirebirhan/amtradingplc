<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use App\Models\User;
use App\Traits\HasBranch;

class Category extends Model
{
    use HasFactory, HasBranch;

    protected $fillable = [
        'name',
        'code',
        'slug',
        'description',
        'parent_id',
        'branch_id',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::deleting(function ($category) {
            if ($category->items()->exists()) {
                throw new \Exception('Cannot delete category: It contains items. Please move or delete all items first.');
            }
            
            if ($category->children()->exists()) {
                throw new \Exception('Cannot delete category: It has subcategories. Please delete subcategories first.');
            }
        });
    }

    public function setNameAttribute($value)
    {
        $this->attributes['name'] = $value;
        if (!$this->exists || !$this->slug) {
            $this->attributes['slug'] = Str::slug($value);
        }
    }

    public function setCodeAttribute($value)
    {
        if ($value) {
            $this->attributes['code'] = strtoupper(Str::substr($value, 0, 15));
        }
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(Item::class);
    }

    public function getPathAttribute()
    {
        $path = [$this->name];
        $category = $this;

        while ($category->parent) {
            $category = $category->parent;
            array_unshift($path, $category->name);
        }

        return implode(' > ', $path);
    }

    public function scopeForUser($query, User $user = null)
    {
        if (!$user) {
            $user = auth()->user();
        }

        if (!$user) {
            return $query;
        }

        if ($user->isSuperAdmin() || $user->isGeneralManager()) {
            return $query;
        }

        if ($user->branch_id) {
            return $query->forBranch($user->branch_id);
        }

        return $query;
    }

    public function getAllDescendantsAttribute()
    {
        $descendants = collect();
        
        foreach ($this->children as $child) {
            $descendants->push($child);
            $descendants = $descendants->merge($child->all_descendants);
        }
        
        return $descendants;
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
