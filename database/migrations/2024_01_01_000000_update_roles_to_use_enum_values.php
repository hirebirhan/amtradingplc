<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Enums\UserRole;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update role names in the roles table to match enum values
        $roleMapping = [
            'admin' => UserRole::SUPER_ADMIN->value,
            'manager' => UserRole::MANAGER->value,
            'staff' => UserRole::WAREHOUSE_USER->value,
            'SystemAdmin' => UserRole::SYSTEM_ADMIN->value,
            'SuperAdmin' => UserRole::SUPER_ADMIN->value,
            'GeneralManager' => UserRole::GENERAL_MANAGER->value,
            'BranchManager' => UserRole::BRANCH_MANAGER->value,
            'WarehouseManager' => UserRole::WAREHOUSE_MANAGER->value,
            'WarehouseUser' => UserRole::WAREHOUSE_USER->value,
            'Sales' => UserRole::SALES->value,
            'Accountant' => UserRole::ACCOUNTANT->value,
            'CustomerService' => UserRole::CUSTOMER_SERVICE->value,
            'PurchaseOfficer' => UserRole::PURCHASE_OFFICER->value,
        ];

        foreach ($roleMapping as $oldName => $newName) {
            DB::table('roles')
                ->where('name', $oldName)
                ->update(['name' => $newName]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverse the role name changes
        $reverseMapping = [
            UserRole::SUPER_ADMIN->value => 'SuperAdmin',
            UserRole::MANAGER->value => 'manager',
            UserRole::WAREHOUSE_USER->value => 'WarehouseUser',
            UserRole::SYSTEM_ADMIN->value => 'SystemAdmin',
            UserRole::GENERAL_MANAGER->value => 'GeneralManager',
            UserRole::BRANCH_MANAGER->value => 'BranchManager',
            UserRole::WAREHOUSE_MANAGER->value => 'WarehouseManager',
            UserRole::SALES->value => 'Sales',
            UserRole::ACCOUNTANT->value => 'Accountant',
            UserRole::CUSTOMER_SERVICE->value => 'CustomerService',
            UserRole::PURCHASE_OFFICER->value => 'PurchaseOfficer',
        ];

        foreach ($reverseMapping as $newName => $oldName) {
            DB::table('roles')
                ->where('name', $newName)
                ->update(['name' => $oldName]);
        }
    }
};