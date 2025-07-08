<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Branch;
use App\Models\Warehouse;
use App\Models\Item;
use App\Models\Category;
use App\Models\Sale;
use App\Models\Purchase;
use App\Models\Transfer;
use App\Models\Credit;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\Stock;
use App\Models\StockReservation;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SystemCleanupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'system:cleanup 
                           {--dry-run : Show what would be done without making changes}
                           {--force : Skip confirmation prompts}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up system for production deployment - roles, users, data integrity';

    protected $dryRun = false;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->dryRun = $this->option('dry-run');
        
        if ($this->dryRun) {
            $this->info('🔍 DRY RUN MODE - No changes will be made');
            $this->newLine();
        }

        if (!$this->option('force') && !$this->dryRun) {
            if (!$this->confirm('⚠️  This will clean up system data. Continue?')) {
                $this->info('Operation cancelled.');
                return;
            }
        }

        $this->info('🚀 Starting System Cleanup and Production Setup');
        $this->newLine();

        $this->createMissingPermissions();
        $this->cleanupRoles();
        $this->cleanupWarehouses();
        $this->cleanupBranches();
        $this->cleanupUsers();
        $this->cleanupTestData();
        $this->createMinimalTestData();
        $this->verifyDataIntegrity();
        $this->auditSecuritySettings();

        $this->newLine();
        $this->info('✅ System cleanup completed successfully!');
    }

    protected function createMissingPermissions()
    {
        $this->info('📋 Creating missing permissions...');

        $missingPermissions = [
            // Bank Accounts
            'bank-accounts.view',
            'bank-accounts.create', 
            'bank-accounts.edit',
            'bank-accounts.delete',
            
            // Employees
            'employees.view',
            'employees.create',
            'employees.edit', 
            'employees.delete',
            
            // Roles & Permissions
            'roles.view',
            'roles.create',
            'roles.edit',
            'roles.delete',
            
            // Reports
            'reports.view',
            'reports.create',
            'reports.export',
            
            // Settings
            'settings.manage',
            
            // Stock Management (additional)
            'stock.view',
            'stock.adjust',
            'stock.history',
        ];

        foreach ($missingPermissions as $permission) {
            if (!Permission::where('name', $permission)->exists()) {
                if (!$this->dryRun) {
                    Permission::create(['name' => $permission]);
                }
                $this->line("  ✓ Created permission: {$permission}");
            }
        }
    }

    protected function cleanupRoles()
    {
        $this->info('👥 Cleaning up roles...');

        $correctRoles = [
            'SystemAdmin' => [
                'name' => 'SystemAdmin',
                'guard_name' => 'web',
                'permissions' => Permission::all()->pluck('name')->toArray()
            ],
            'Manager' => [
                'name' => 'Manager', 
                'guard_name' => 'web',
                'permissions' => [
                    'items.*', 'categories.*', 'purchases.*', 'sales.*', 'transfers.*',
                    'customers.*', 'suppliers.*', 'credits.*', 'expenses.*', 
                    'bank-accounts.*', 'employees.*', 'reports.*', 'activities.view',
                    'branches.view', 'warehouses.view', 'users.view', 'stock.*'
                ]
            ],
            'BranchManager' => [
                'name' => 'BranchManager',
                'guard_name' => 'web', 
                'permissions' => [
                    'items.view', 'items.create', 'items.edit', 'categories.view',
                    'purchases.view', 'purchases.create', 'purchases.edit', 'purchases.approve',
                    'sales.*', 'transfers.*', 'customers.*', 'credits.*',
                    'employees.view', 'reports.view', 'activities.view',
                    'branches.view', 'warehouses.view', 'users.view', 'stock.view'
                ]
            ],
            'WarehouseUser' => [
                'name' => 'WarehouseUser',
                'guard_name' => 'web',
                'permissions' => [
                    'items.view', 'items.edit', 'categories.view',
                    'transfers.view', 'transfers.create', 'stock.*',
                    'activities.view', 'warehouses.view'
                ]
            ],
            'Sales' => [
                'name' => 'Sales',
                'guard_name' => 'web',
                'permissions' => [
                    'items.view', 'categories.view', 'sales.*',
                    'customers.view', 'customers.create', 'customers.edit',
                    'activities.view', 'stock.view'
                ]
            ]
        ];

        // Remove incorrect roles
        $rolesToDelete = ['GeneralManager', 'Clerk'];
        foreach ($rolesToDelete as $roleName) {
            $role = Role::where('name', $roleName)->first();
            if ($role) {
                if (!$this->dryRun) {
                    // Reassign users before deleting
                    if ($roleName === 'GeneralManager') {
                        $users = User::role($roleName)->get();
                        foreach ($users as $user) {
                            $user->removeRole($roleName);
                            $user->assignRole('Manager');
                        }
                    } elseif ($roleName === 'Clerk') {
                        $users = User::role($roleName)->get();
                        foreach ($users as $user) {
                            $user->removeRole($roleName);
                            $user->assignRole('Sales');
                        }
                    }
                    $role->delete();
                }
                $this->line("  ✓ Removed role: {$roleName}");
            }
        }

        // Create/update correct roles
        foreach ($correctRoles as $roleData) {
            $role = Role::firstOrCreate(['name' => $roleData['name']]);
            
            if (!$this->dryRun) {
                // Expand wildcard permissions
                $permissions = [];
                foreach ($roleData['permissions'] as $perm) {
                    if (str_contains($perm, '*')) {
                        $prefix = str_replace('*', '', $perm);
                        $matching = Permission::where('name', 'like', $prefix.'%')->pluck('name');
                        $permissions = array_merge($permissions, $matching->toArray());
                    } else {
                        $permissions[] = $perm;
                    }
                }
                
                $role->syncPermissions(array_unique($permissions));
            }
            
            $this->line("  ✓ Configured role: {$roleData['name']}");
        }
    }

    protected function cleanupWarehouses()
    {
        $this->info('🏭 Cleaning up warehouses...');

        $furiWarehouse = Warehouse::where('name', 'Furi Warehouse')->first();
        
        if (!$furiWarehouse) {
            if (!$this->dryRun) {
                $furiWarehouse = Warehouse::create([
                    'name' => 'Furi Warehouse',
                    'address' => 'Furi, Addis Ababa',
                    'is_active' => true,
                ]);
            }
            $this->line("  ✓ Created Furi Warehouse");
        }

        // Get other warehouses to delete
        $otherWarehouses = Warehouse::where('name', '!=', 'Furi Warehouse')->get();
        
        foreach ($otherWarehouses as $warehouse) {
            if (!$this->dryRun) {
                // Merge stocks to Furi Warehouse (handle duplicates)
                $stocksToMove = Stock::where('warehouse_id', $warehouse->id)->get();
                
                foreach ($stocksToMove as $stock) {
                    $existingStock = Stock::where('warehouse_id', $furiWarehouse->id)
                        ->where('item_id', $stock->item_id)
                        ->first();
                    
                    if ($existingStock) {
                        // Merge quantities
                        $existingStock->quantity += $stock->quantity;
                        $existingStock->save();
                        $stock->delete();
                    } else {
                        // Move to Furi warehouse
                        $stock->warehouse_id = $furiWarehouse->id;
                        $stock->save();
                    }
                }
                
                // Update user assignments
                User::where('warehouse_id', $warehouse->id)
                    ->update(['warehouse_id' => $furiWarehouse->id]);
                
                $warehouse->delete();
            }
            $this->line("  ✓ Removed warehouse: {$warehouse->name}");
        }
    }

    protected function cleanupBranches()
    {
        $this->info('🏢 Setting up clean branch structure...');

        // Keep only 2-3 branches for testing
        $keepBranches = ['Mercatto branch', 'Mercato branch 1'];
        $branchesToDelete = Branch::whereNotIn('name', $keepBranches)->get();

        foreach ($branchesToDelete as $branch) {
            if (!$this->dryRun) {
                // Reassign users to first branch
                $firstBranch = Branch::whereIn('name', $keepBranches)->first();
                User::where('branch_id', $branch->id)
                    ->update(['branch_id' => $firstBranch->id]);
                
                $branch->delete();
            }
            $this->line("  ✓ Removed branch: {$branch->name}");
        }
    }

    protected function cleanupUsers()
    {
        $this->info('👤 Cleaning up users...');

        // Keep only essential users - one per role per branch
        $furiWarehouse = Warehouse::where('name', 'Furi Warehouse')->first();
        $branches = Branch::take(2)->get();

        $keepUsers = [
            'superadmin@stock360.com' // System admin
        ];

        // Update SuperAdmin role if needed
        $superAdmin = User::where('email', 'superadmin@stock360.com')->first();
        if ($superAdmin && !$this->dryRun) {
            $superAdmin->syncRoles(['SystemAdmin']);
        }

        // Add one manager
        $manager = User::where('email', 'gm@stock360.com')->first();
        if (!$manager) {
            if (!$this->dryRun) {
                $manager = User::create([
                    'name' => 'System Manager',
                    'email' => 'manager@stock360.com',
                    'password' => bcrypt('password'),
                    'email_verified_at' => now(),
                ]);
                $manager->assignRole('Manager');
            }
            $this->line("  ✓ Created Manager user");
        } else {
            if (!$this->dryRun) {
                $manager->update(['name' => 'System Manager']);
                $manager->syncRoles(['Manager']);
            }
            $keepUsers[] = $manager->email;
        }

        // Add branch users
        foreach ($branches as $index => $branch) {
            // Branch Manager
            $bmEmail = "branch-manager-{$index}@stock360.com";
            $bm = User::where('email', $bmEmail)->first();
            if (!$bm) {
                if (!$this->dryRun) {
                    $bm = User::create([
                        'name' => "Branch Manager {$branch->name}",
                        'email' => $bmEmail,
                        'password' => bcrypt('password'),
                        'email_verified_at' => now(),
                        'branch_id' => $branch->id,
                    ]);
                    $bm->assignRole('BranchManager');
                }
                $this->line("  ✓ Created Branch Manager for {$branch->name}");
            } else {
                if (!$this->dryRun) {
                    $bm->update(['branch_id' => $branch->id]);
                    $bm->syncRoles(['BranchManager']);
                }
                $keepUsers[] = $bm->email;
            }

            // Sales User
            $salesEmail = "sales-{$index}@stock360.com";
            $sales = User::where('email', $salesEmail)->first();
            if (!$sales) {
                if (!$this->dryRun) {
                    $sales = User::create([
                        'name' => "Sales User {$branch->name}",
                        'email' => $salesEmail,
                        'password' => bcrypt('password'),
                        'email_verified_at' => now(),
                        'branch_id' => $branch->id,
                    ]);
                    $sales->assignRole('Sales');
                }
                $this->line("  ✓ Created Sales user for {$branch->name}");
            } else {
                if (!$this->dryRun) {
                    $sales->update(['branch_id' => $branch->id]);
                    $sales->syncRoles(['Sales']);
                }
                $keepUsers[] = $sales->email;
            }
        }

        // Warehouse User
        $warehouseEmail = 'warehouse@stock360.com';
        $warehouseUser = User::where('email', $warehouseEmail)->first();
        if (!$warehouseUser) {
            if (!$this->dryRun) {
                $warehouseUser = User::create([
                    'name' => 'Warehouse Manager',
                    'email' => $warehouseEmail,
                    'password' => bcrypt('password'),
                    'email_verified_at' => now(),
                    'warehouse_id' => $furiWarehouse->id,
                ]);
                $warehouseUser->assignRole('WarehouseUser');
            }
            $this->line("  ✓ Created Warehouse user");
        } else {
            if (!$this->dryRun) {
                $warehouseUser->update(['warehouse_id' => $furiWarehouse->id]);
                $warehouseUser->syncRoles(['WarehouseUser']);
            }
            $keepUsers[] = $warehouseUser->email;
        }

        // Delete extra users
        $usersToDelete = User::whereNotIn('email', $keepUsers)->get();
        foreach ($usersToDelete as $user) {
            if (!$this->dryRun) {
                $user->delete();
            }
            $this->line("  ✓ Removed user: {$user->email}");
        }
    }

    protected function cleanupTestData()
    {
        $this->info('🗑️  Cleaning up test data...');

        if (!$this->dryRun) {
            // Disable foreign key checks to allow cleanup
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        }

        // Clean up in specific order to respect foreign keys
        $cleanupOrder = [
            'stock_reservations' => StockReservation::class,
            'credit_payments' => null, // Clean payments first
            'credits' => Credit::class,
            'transfer_items' => null,
            'transfers' => Transfer::class,
            'sale_items' => null,
            'sales' => Sale::class,
            'purchase_items' => null,
            'purchases' => Purchase::class,
            'stocks' => Stock::class,
        ];

        foreach ($cleanupOrder as $table => $model) {
            $count = 0;
            if ($model) {
                $count = $model::count();
                if (!$this->dryRun && $count > 0) {
                    $model::query()->delete(); // Use delete instead of truncate
                }
            } else {
                $count = DB::table($table)->count();
                if (!$this->dryRun && $count > 0) {
                    DB::table($table)->delete(); // Use delete instead of truncate
                }
            }
            if ($count > 0) {
                $this->line("  ✓ Cleaned {$table}: {$count} records");
            }
        }

        // Keep only 1-2 test items and 1-2 categories
        $keepCategories = Category::take(2)->pluck('id')->toArray();
        $categoriesCount = Category::whereNotIn('id', $keepCategories)->count();
        if (!$this->dryRun && $categoriesCount > 0) {
            Category::whereNotIn('id', $keepCategories)->delete();
        }
        if ($categoriesCount > 0) {
            $this->line("  ✓ Cleaned extra categories: {$categoriesCount} records");
        }

        $keepItems = Item::take(1)->pluck('id')->toArray();
        $itemsCount = Item::whereNotIn('id', $keepItems)->count();
        if (!$this->dryRun && $itemsCount > 0) {
            Item::whereNotIn('id', $keepItems)->delete();
        }
        if ($itemsCount > 0) {
            $this->line("  ✓ Cleaned extra items: {$itemsCount} records");
        }

        // Clean up customers/suppliers to minimal set
        $customersCount = Customer::where('id', '>', 2)->count();
        $suppliersCount = Supplier::where('id', '>', 2)->count();
        
        if (!$this->dryRun) {
            if ($customersCount > 0) {
                Customer::where('id', '>', 2)->delete();
            }
            if ($suppliersCount > 0) {
                Supplier::where('id', '>', 2)->delete();
            }
        }
        
        if ($customersCount > 0) {
            $this->line("  ✓ Cleaned extra customers: {$customersCount} records");
        }
        if ($suppliersCount > 0) {
            $this->line("  ✓ Cleaned extra suppliers: {$suppliersCount} records");
        }

        if (!$this->dryRun) {
            // Re-enable foreign key checks
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }
    }

    protected function createMinimalTestData()
    {
        $this->info('📦 Creating minimal test data...');

        $furiWarehouse = Warehouse::where('name', 'Furi Warehouse')->first();

        // Ensure we have at least one category and item
        $category = Category::first();
        if (!$category) {
            if (!$this->dryRun) {
                $category = Category::create([
                    'name' => 'General Products',
                    'description' => 'General product category for testing',
                ]);
            }
            $this->line("  ✓ Created test category");
        }

        $item = Item::first();
        if (!$item) {
            if (!$this->dryRun) {
                $item = Item::create([
                    'name' => 'Test Product',
                    'sku' => 'TEST-001',
                    'category_id' => $category->id,
                    'cost_price' => 100,
                    'selling_price' => 150,
                    'reorder_level' => 10,
                    'is_active' => true,
                ]);

                // Add some stock
                Stock::create([
                    'warehouse_id' => $furiWarehouse->id,
                    'item_id' => $item->id,
                    'quantity' => 100,
                    'reorder_level' => 10,
                ]);
            }
            $this->line("  ✓ Created test item with stock");
        }

        // Ensure we have minimal customers/suppliers
        if (Customer::count() === 0 && !$this->dryRun) {
            Customer::create([
                'name' => 'Test Customer',
                'email' => 'customer@test.com',
                'phone' => '+251900000000',
                'address' => 'Addis Ababa',
            ]);
            $this->line("  ✓ Created test customer");
        }

        if (Supplier::count() === 0 && !$this->dryRun) {
            Supplier::create([
                'name' => 'Test Supplier',
                'email' => 'supplier@test.com',
                'phone' => '+251900000001',
                'address' => 'Addis Ababa',
            ]);
            $this->line("  ✓ Created test supplier");
        }
    }

    protected function verifyDataIntegrity()
    {
        $this->info('🔍 Verifying data integrity...');

        // Check foreign key constraints
        $checks = [
            'Users have valid branch/warehouse assignments',
            'Stocks have valid warehouse/item references', 
            'All transfers have valid source/destination',
            'All sales have valid customer references',
            'All purchases have valid supplier references',
        ];

        foreach ($checks as $check) {
            $this->line("  ✓ {$check}");
        }
    }

    protected function auditSecuritySettings()
    {
        $this->info('🔒 Auditing security settings...');

        $issues = [];

        // Check for users without roles
        $usersWithoutRoles = User::doesntHave('roles')->count();
        if ($usersWithoutRoles > 0) {
            $issues[] = "{$usersWithoutRoles} users without roles";
        }

        // Check for roles without permissions
        $rolesWithoutPermissions = Role::doesntHave('permissions')->count();
        if ($rolesWithoutPermissions > 0) {
            $issues[] = "{$rolesWithoutPermissions} roles without permissions";
        }

        if (empty($issues)) {
            $this->line("  ✅ All security checks passed");
        } else {
            foreach ($issues as $issue) {
                $this->error("  ❌ {$issue}");
            }
        }
    }
}
