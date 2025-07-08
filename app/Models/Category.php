<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use App\Models\User;

class Category extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
'name',
        'code',
        'slug',
        'description',
        'parent_id',
        'is_active',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Generate a URL friendly slug from the name.
     */
    public function setNameAttribute($value)
    {
        $this->attributes['name'] = $value;
        $this->attributes['slug'] = Str::slug($value);
    }

    /**
     * Ensure code is properly formatted and within length limits.
     */
    public function setCodeAttribute($value)
    {
        if ($value) {
            // Ensure code is uppercase and doesn't exceed 15 characters
            $this->attributes['code'] = strtoupper(Str::substr($value, 0, 15));
        }
    }

    /**
     * Get the parent category.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    /**
     * Get the child categories.
     */
    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    /**
     * Get all items in this category.
     */
    public function items(): HasMany
    {
        return $this->hasMany(Item::class);
    }

    /**
     * Get the full hierarchical path of the category.
     */
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

    /**
     * Scope a query to only include active categories.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get all descendants of the category.
     */
    public function getAllDescendantsAttribute()
    {
        $descendants = collect();
        
        foreach ($this->children as $child) {
            $descendants->push($child);
            $descendants = $descendants->merge($child->all_descendants);
        }
        
        return $descendants;
    }

    /**
     * Check if category can be safely deleted
     */
    public function canBeDeleted()
    {
        return $this->items_count === 0 && $this->children_count === 0;
    }

    /**
     * Custom delete behavior for categories
     * Override the delete method to handle constraints
     */
    public function delete()
    {
        // We'll allow deletion even with constraints, but in the UI
        // we show warnings about what will happen
        return parent::delete();
    }

    /**
     * Get the user who created this record.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this record.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the user who deleted this record.
     */
    public function deleter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }
}
