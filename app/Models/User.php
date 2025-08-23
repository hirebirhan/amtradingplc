<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;
use App\Enums\UserRole;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'branch_id',
        'warehouse_id',
        'phone',
        'position',
        'is_active',
        'last_login_at',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean',
        'last_login_at' => 'datetime',
    ];

    /**
     * Get the branch associated with the user.
     */
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the warehouse associated with the user.
     */
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Get the employee associated with the user.
     */
    public function employee()
    {
        return $this->hasOne(Employee::class);
    }

    /**
     * Determine if the user is a super admin.
     */
    public function isSuperAdmin(): bool
    {
        return $this->hasRole(UserRole::SUPER_ADMIN->value);
    }

    /**
     * Determine if the user is a general manager.
     * 
     * @deprecated This role doesn't exist in the system anymore
     * @return bool Always returns false
     */
    public function isGeneralManager(): bool
    {
        return false;
    }

    /**
     * Determine if the user is a branch manager.
     */
    public function isBranchManager(): bool
    {
        return $this->hasRole(UserRole::BRANCH_MANAGER->value);
    }

    /**
     * Determine if the user is a warehouse user.
     */
    public function isWarehouseUser(): bool
    {
        return $this->hasRole(UserRole::WAREHOUSE_MANAGER->value);
    }

    /**
     * Determine if the user is a sales person.
     */
    public function isSales(): bool
    {
        return $this->hasRole(UserRole::SALES->value);
    }

    /**
     * Determine if the user can manage stock reservations.
     */
    public function canManageStockReservations(): bool
    {
        return $this->isSuperAdmin() || $this->can('transfers.edit');
    }

    /**
     * Get the user's role names as an array.
     */
    public function getRoleNames(): array
    {
        return $this->roles->pluck('name')->toArray();
    }

    /**
     * Determine if the user can access branch and warehouse filters.
     */
    public function canAccessLocationFilters(): bool
    {
        // Get current user's role names
        $userRoles = $this->getRoleNames();
        
        // Define roles that can access location filters
        $adminRoles = [
            UserRole::SUPER_ADMIN->value,
            UserRole::BRANCH_MANAGER->value,
            UserRole::WAREHOUSE_MANAGER->value
        ];
        
        // Check if user has any admin role
        return !empty(array_intersect($userRoles, $adminRoles));
    }

    /**
     * Get user's assignment type and name
     */
    public function getAssignmentAttribute(): string
    {
        if ($this->branch && $this->warehouse) {
            return "Branch: {$this->branch->name}, Warehouse: {$this->warehouse->name}";
        } elseif ($this->branch) {
            return "Branch: {$this->branch->name}";
        } elseif ($this->warehouse) {
            return "Warehouse: {$this->warehouse->name}";
        }
        
        return 'No assignment';
    }

    /**
     * Check if user has access to a specific branch
     */
    public function hasAccessToBranch($branchId): bool
    {
        // Super admins and General Managers have access to all branches
        if ($this->isSuperAdmin()) {
            return true;
        }

        // Users assigned to the branch
        if ($this->branch_id == $branchId) {
            return true;
        }

        // Users assigned to a warehouse within the branch
        if ($this->warehouse && $this->warehouse->branches->contains('id', $branchId)) {
            return true;
        }

        return false;
    }

    /**
     * Check if user has access to a specific warehouse
     */
    public function hasAccessToWarehouse($warehouseId): bool
    {
        // Super admins and General Managers have access to all warehouses
        if ($this->isSuperAdmin()) {
            return true;
        }

        // Users assigned to the warehouse
        if ($this->warehouse_id == $warehouseId) {
            return true;
        }

        // Branch managers have access to warehouses in their branch
        if ($this->hasRole('BranchManager') && $this->branch) {
            return $this->branch->warehouses->contains('id', $warehouseId);
        }

        return false;
    }

    /**
     * Update last login timestamp
     */
    public function updateLastLogin(): void
    {
        $this->update(['last_login_at' => now()]);
    }

    /**
     * Scope to filter active users
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter users by role
     */
    public function scopeWithRole($query, $role)
    {
        return $query->whereHas('roles', function ($q) use ($role) {
            $q->where('name', $role);
        });
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
