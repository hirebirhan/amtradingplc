<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Category;
use App\Models\Item;
use App\Models\Branch;
use App\Models\Warehouse;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ComprehensiveSystemValidation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'system:validate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Comprehensive system validation for production readiness';

    private $errors = [];
    private $warnings = [];
    private $successes = [];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 COMPREHENSIVE SYSTEM VALIDATION');
        $this->info('=====================================');
        $this->newLine();

        $this->validateDatabaseSchema();
        $this->validateRolesAndPermissions();
        $this->validateUserStructure();
        $this->validateDataIntegrity();
        $this->validateAuditSystem();
        $this->validateCriticalFunctionality();

        $this->displayResults();
        
        return count($this->errors) === 0 ? 0 : 1;
    }

    protected function validateDatabaseSchema()
    {
        $this->info('📋 Validating Database Schema...');

        // Check audit fields on all critical tables
        $criticalTables = ['users', 'items', 'categories', 'sales', 'purchases', 'transfers', 'credits', 'customers', 'suppliers', 'stocks', 'branches', 'warehouses'];
        
        foreach ($criticalTables as $table) {
            if (!Schema::hasTable($table)) {
                $this->errors[] = "Table {$table} does not exist";
                continue;
            }

            $auditFields = ['created_by', 'updated_by', 'deleted_by'];
            $missingFields = [];
            
            foreach ($auditFields as $field) {
                if (!Schema::hasColumn($table, $field)) {
                    $missingFields[] = $field;
                }
            }
            
            if (empty($missingFields)) {
                $this->successes[] = "Table {$table}: All audit fields present";
            } else {
                $this->errors[] = "Table {$table}: Missing audit fields: " . implode(', ', $missingFields);
            }
        }
    }

    protected function validateRolesAndPermissions()
    {
        $this->info('👥 Validating Roles & Permissions...');

        // Check required roles exist
        $requiredRoles = ['SystemAdmin', 'Manager', 'BranchManager', 'WarehouseUser', 'Sales'];
        foreach ($requiredRoles as $roleName) {
            $role = Role::where('name', $roleName)->first();
            if (!$role) {
                $this->errors[] = "Required role missing: {$roleName}";
            } else {
                $permissionCount = $role->permissions()->count();
                if ($permissionCount > 0) {
                    $this->successes[] = "Role {$roleName}: {$permissionCount} permissions assigned";
                } else {
                    $this->errors[] = "Role {$roleName}: No permissions assigned";
                }
            }
        }

        // Check total permissions count
        $totalPermissions = Permission::count();
        if ($totalPermissions >= 80) {
            $this->successes[] = "Permissions system: {$totalPermissions} permissions available";
        } else {
            $this->warnings[] = "Only {$totalPermissions} permissions found (expected 80+)";
        }
    }

    protected function validateUserStructure()
    {
        $this->info('👤 Validating User Structure...');

        $totalUsers = User::count();
        
        // Check SystemAdmin exists
        $systemAdmin = User::role('SystemAdmin')->first();
        if ($systemAdmin) {
            $this->successes[] = "SystemAdmin user exists: {$systemAdmin->email}";
        } else {
            $this->errors[] = "No SystemAdmin user found";
        }

        // Check user assignments
        $usersWithRoles = User::has('roles')->count();
        $usersWithoutRoles = $totalUsers - $usersWithRoles;
        
        if ($usersWithoutRoles === 0) {
            $this->successes[] = "All {$totalUsers} users have roles assigned";
        } else {
            $this->errors[] = "{$usersWithoutRoles} users without roles";
        }

        // Check branch/warehouse assignments
        $branchManagers = User::role('BranchManager')->whereNull('branch_id')->count();
        $warehouseUsers = User::role('WarehouseUser')->whereNull('warehouse_id')->count();
        
        if ($branchManagers === 0) {
            $this->successes[] = "All BranchManagers have branch assignments";
        } else {
            $this->errors[] = "{$branchManagers} BranchManagers without branch assignment";
        }
        
        if ($warehouseUsers === 0) {
            $this->successes[] = "All WarehouseUsers have warehouse assignments";
        } else {
            $this->errors[] = "{$warehouseUsers} WarehouseUsers without warehouse assignment";
        }
    }

    protected function validateDataIntegrity()
    {
        $this->info('🗃️  Validating Data Integrity...');

        // Check warehouse count
        $warehouseCount = Warehouse::count();
        if ($warehouseCount === 1) {
            $warehouse = Warehouse::first();
            if ($warehouse->name === 'Furi Warehouse') {
                $this->successes[] = "Single warehouse setup: {$warehouse->name}";
            } else {
                $this->warnings[] = "Single warehouse but not named 'Furi Warehouse': {$warehouse->name}";
            }
        } else {
            $this->errors[] = "Expected 1 warehouse, found {$warehouseCount}";
        }

        // Check branch count
        $branchCount = Branch::count();
        if ($branchCount >= 1 && $branchCount <= 3) {
            $this->successes[] = "Branch count appropriate for testing: {$branchCount}";
        } else {
            $this->warnings[] = "Branch count: {$branchCount} (consider 1-3 for testing)";
        }

        // Check for clean transaction data
        $salesCount = DB::table('sales')->count();
        $transfersCount = DB::table('transfers')->count();
        $creditsCount = DB::table('credits')->count();
        
        if ($salesCount === 0 && $transfersCount === 0 && $creditsCount === 0) {
            $this->successes[] = "Clean transaction data (no test pollution)";
        } else {
            $this->warnings[] = "Transaction data present: {$salesCount} sales, {$transfersCount} transfers, {$creditsCount} credits";
        }
    }

    protected function validateAuditSystem()
    {
        $this->info('📝 Validating Audit System...');

        try {
            // Test audit functionality
            $testUser = User::first();
            auth()->login($testUser);

            // Test category creation (with unique identifiers)
            $uniqueId = substr(time(), -3);
            $testCategory = Category::create([
                'name' => 'System Validation Test ' . $uniqueId,
                'code' => 'VAL' . $uniqueId,
                'description' => 'Testing audit system'
            ]);

            if ($testCategory->created_by === $testUser->id) {
                $this->successes[] = "Audit observer: created_by field populated correctly";
            } else {
                $this->errors[] = "Audit observer: created_by field not populated";
            }

            // Test update
            $testCategory->update(['description' => 'Updated for validation']);
            $testCategory->refresh();

            if ($testCategory->updated_by === $testUser->id) {
                $this->successes[] = "Audit observer: updated_by field populated correctly";
            } else {
                $this->errors[] = "Audit observer: updated_by field not populated";
            }

            // Test relationships
            if ($testCategory->creator && $testCategory->creator->id === $testUser->id) {
                $this->successes[] = "Audit relationships: creator() working correctly";
            } else {
                $this->errors[] = "Audit relationships: creator() not working";
            }

            if ($testCategory->updater && $testCategory->updater->id === $testUser->id) {
                $this->successes[] = "Audit relationships: updater() working correctly";
            } else {
                $this->errors[] = "Audit relationships: updater() not working";
            }

            // Cleanup
            $testCategory->delete();
            
        } catch (\Exception $e) {
            $this->errors[] = "Audit system test failed: " . $e->getMessage();
        }
    }

    protected function validateCriticalFunctionality()
    {
        $this->info('⚡ Validating Critical Functionality...');

        try {
            // Test user login update (original error scenario)
            $testUser = User::skip(1)->first(); // Get second user
            if ($testUser) {
                auth()->login($testUser);
                $testUser->updateLastLogin();
                $this->successes[] = "User login update: Working correctly (original error fixed)";
            }

            // Test model operations
            $itemCount = Item::count();
            $categoryCount = Category::count();
            
            if ($itemCount > 0 && $categoryCount > 0) {
                $this->successes[] = "Basic models: Items ({$itemCount}) and Categories ({$categoryCount}) exist";
            } else {
                $this->warnings[] = "Very minimal test data: Items ({$itemCount}), Categories ({$categoryCount})";
            }

        } catch (\Exception $e) {
            $this->errors[] = "Critical functionality test failed: " . $e->getMessage();
        }
    }

    protected function displayResults()
    {
        $this->newLine();
        $this->info('📊 VALIDATION RESULTS');
        $this->info('=====================');
        
        if (!empty($this->successes)) {
            $this->newLine();
            $this->info('✅ SUCCESSES (' . count($this->successes) . '):');
            foreach ($this->successes as $success) {
                $this->line("  ✓ {$success}");
            }
        }

        if (!empty($this->warnings)) {
            $this->newLine();
            $this->warn('⚠️  WARNINGS (' . count($this->warnings) . '):');
            foreach ($this->warnings as $warning) {
                $this->line("  ⚠ {$warning}");
            }
        }

        if (!empty($this->errors)) {
            $this->newLine();
            $this->error('❌ ERRORS (' . count($this->errors) . '):');
            foreach ($this->errors as $error) {
                $this->line("  ✗ {$error}");
            }
        }

        $this->newLine();
        
        if (empty($this->errors)) {
            $this->info('🎉 SYSTEM VALIDATION: PASSED');
            $this->info('System is ready for production deployment!');
        } else {
            $this->error('❌ SYSTEM VALIDATION: FAILED');
            $this->error('Please fix the errors above before deployment.');
        }

        $this->newLine();
        $this->info('Summary:');
        $this->line("  Successes: " . count($this->successes));
        $this->line("  Warnings:  " . count($this->warnings));
        $this->line("  Errors:    " . count($this->errors));
    }
}
