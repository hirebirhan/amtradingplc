<?php

namespace App\Livewire\Users;

use App\Models\User;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('layouts.app')]
class Show extends Component
{
    public User $user;

    public function mount(User $user)
    {
        $this->user = $user->load(['roles.permissions', 'branch', 'warehouse', 'employee']);
    }

    /**
     * Get user's direct permissions (not through roles)
     */
    public function getDirectPermissionsProperty()
    {
        return $this->user->getDirectPermissions();
    }

    /**
     * Get all permissions (both from roles and direct)
     */
    public function getAllPermissionsProperty()
    {
        return $this->user->getAllPermissions()->groupBy(function ($permission) {
            return explode('.', $permission->name)[0];
        })->sortKeys();
    }

    /**
     * Get user activity stats
     */
    public function getActivityStatsProperty()
    {
        $stats = [
            'last_login' => $this->user->last_login_at ?? 'Never',
            'created_date' => $this->user->created_at->format('M d, Y'),
            'total_roles' => $this->user->roles->count(),
            'total_permissions' => $this->user->getAllPermissions()->count(),
        ];

        return $stats;
    }

    /**
     * Get resource icon
     */
    public function getResourceIcon($resource)
    {
        $icons = [
            'users' => 'users',
            'branches' => 'building',
            'warehouses' => 'warehouse',
            'categories' => 'tags',
            'items' => 'box',
            'employees' => 'id-card',
            'roles' => 'shield-alt',
            'stock' => 'boxes',
            'purchases' => 'shopping-cart',
            'sales' => 'cash-register',
            'returns' => 'undo',
            'transfers' => 'exchange-alt',
            'reports' => 'chart-bar',
            'settings' => 'cog'
        ];
        
        return $icons[$resource] ?? 'circle';
    }

    public function render()
    {
        return view('livewire.users.show', [
            'directPermissions' => $this->directPermissions,
            'allPermissions' => $this->allPermissions,
            'activityStats' => $this->activityStats,
        ])->title('User Details - ' . $this->user->name);
    }
}
