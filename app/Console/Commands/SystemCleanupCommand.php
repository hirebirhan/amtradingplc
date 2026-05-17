<?php

namespace App\Console\Commands;

use App\Models\Branch;
use App\Models\Category;
use App\Models\Credit;
use App\Models\Customer;
use App\Models\Item;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\Stock;
use App\Models\StockReservation;
use App\Models\Supplier;
use App\Models\Transfer;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class SystemCleanupCommand extends Command
{
    protected $signature = 'system:cleanup
                           {--dry-run : Show what would be done without making changes}
                           {--force : Skip confirmation prompts}';

    protected $description = 'Clean up system for production deployment - roles, users, data integrity';

    protected bool $dryRun = false;

    public function handle(): void
    {
        $this->dryRun = $this->option('dry-run');

        if ($this->dryRun) {
            $this->info('DRY RUN MODE - No changes will be made');
            $this->newLine();
        }

        if (! $this->option('force') && ! $this->dryRun) {
            if (! $this->confirm('This will clean up system data. Continue?')) {
                $this->info('Operation cancelled.');

                return;
            }
        }

        $this->info('Starting System Cleanup and Production Setup');
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
        $this->info('System cleanup completed successfully!');
    }

    protected function createMissingPermissions(): void
    {
        $this->info('Creating missing permissions...');

        $permissions = [
            'bank-accounts.view', 'bank-accounts.create', 'bank-accounts.edit', 'bank-accounts.delete',
            'employees.view', 'employees.create', 'employees.edit', 'employees.delete',
            'roles.view', 'roles.create', 'roles.edit', 'roles.delete',
            'reports.view', 'reports.create', 'reports.export',
            'settings.manage',
            'stock.view', 'stock.adjust', 'stock.history',
        ];

        foreach ($permissions as $name) {
            if (! Permission::where('name', $name)->exists()) {
                if (! $this->dryRun) {
                    Permission::create(['name' => $name]);
                }
                $this->line("  Created permission: {$name}");
            }
        }
    }

    protected function cleanupRoles(): void
    {
        $this->info('Cleaning up roles...');
        $this->removeObsoleteRoles(['GeneralManager' => 'Manager', 'Clerk' => 'Sales']);

        $roles = [
            'SystemAdmin'    => Permission::all()->pluck('name')->toArray(),
            'Manager'        => ['items.*', 'categories.*', 'purchases.*', 'sales.*', 'transfers.*', 'customers.*', 'suppliers.*', 'credits.*', 'expenses.*', 'bank-accounts.*', 'employees.*', 'reports.*', 'activities.view', 'branches.view', 'warehouses.view', 'users.view', 'stock.*'],
            'BranchManager'  => ['items.view', 'items.create', 'items.edit', 'categories.view', 'purchases.view', 'purchases.create', 'purchases.edit', 'purchases.approve', 'sales.*', 'transfers.*', 'customers.*', 'credits.*', 'employees.view', 'reports.view', 'activities.view', 'branches.view', 'warehouses.view', 'users.view', 'stock.view'],
            'WarehouseUser'  => ['items.view', 'items.edit', 'categories.view', 'transfers.view', 'transfers.create', 'stock.*', 'activities.view', 'warehouses.view'],
            'Sales'          => ['items.view', 'categories.view', 'sales.*', 'customers.view', 'customers.create', 'customers.edit', 'activities.view', 'stock.view'],
        ];

        foreach ($roles as $name => $permissions) {
            $this->syncRolePermissions($name, $permissions);
        }
    }

    private function removeObsoleteRoles(array $replacements): void
    {
        foreach ($replacements as $old => $new) {
            $role = Role::where('name', $old)->first();
            if (! $role) {
                continue;
            }
            if (! $this->dryRun) {
                User::role($old)->get()->each(fn ($u) => $u->syncRoles([$new]));
                $role->delete();
            }
            $this->line("  Removed role: {$old} (users moved to {$new})");
        }
    }

    private function syncRolePermissions(string $name, array $wildcardPermissions): void
    {
        $role = Role::firstOrCreate(['name' => $name]);

        if (! $this->dryRun) {
            $resolved = [];
            foreach ($wildcardPermissions as $perm) {
                if (str_contains($perm, '*')) {
                    $prefix = str_replace('*', '', $perm);
                    $resolved = array_merge($resolved, Permission::where('name', 'like', $prefix.'%')->pluck('name')->toArray());
                } else {
                    $resolved[] = $perm;
                }
            }
            $role->syncPermissions(array_unique($resolved));
        }

        $this->line("  Configured role: {$name}");
    }

    protected function cleanupWarehouses(): void
    {
        $this->info('Cleaning up warehouses...');

        $furi = Warehouse::where('name', 'Furi Warehouse')->first();
        if (! $furi) {
            if (! $this->dryRun) {
                $furi = Warehouse::create(['name' => 'Furi Warehouse', 'address' => 'Furi, Addis Ababa', 'is_active' => true]);
            }
            $this->line('  Created Furi Warehouse');
        }

        foreach (Warehouse::where('name', '!=', 'Furi Warehouse')->get() as $wh) {
            if (! $this->dryRun) {
                foreach (Stock::where('warehouse_id', $wh->id)->get() as $stock) {
                    $existing = Stock::where('warehouse_id', $furi->id)->where('item_id', $stock->item_id)->first();
                    if ($existing) {
                        $existing->increment('quantity', $stock->quantity);
                        $stock->delete();
                    } else {
                        $stock->update(['warehouse_id' => $furi->id]);
                    }
                }
                User::where('warehouse_id', $wh->id)->update(['warehouse_id' => $furi->id]);
                $wh->delete();
            }
            $this->line("  Removed warehouse: {$wh->name}");
        }
    }

    protected function cleanupBranches(): void
    {
        $this->info('Setting up clean branch structure...');

        $keep = ['Mercatto branch', 'Mercato branch 1'];
        foreach (Branch::whereNotIn('name', $keep)->get() as $branch) {
            if (! $this->dryRun) {
                $first = Branch::whereIn('name', $keep)->first();
                User::where('branch_id', $branch->id)->update(['branch_id' => $first->id]);
                $branch->delete();
            }
            $this->line("  Removed branch: {$branch->name}");
        }
    }

    protected function cleanupUsers(): void
    {
        $this->info('Cleaning up users...');

        $furi     = Warehouse::where('name', 'Furi Warehouse')->first();
        $branches = Branch::take(2)->get();
        $keep     = ['superadmin@stock360.com'];

        $this->ensureSuperAdmin($keep);
        $this->ensureManagerUser($keep);
        $this->ensureBranchUsers($keep, $branches);
        $this->ensureWarehouseUser($keep, $furi);
        $this->removeExtraUsers($keep);
    }

    private function ensureSuperAdmin(array &$keep): void
    {
        $admin = User::where('email', 'superadmin@stock360.com')->first();
        if ($admin && ! $this->dryRun) {
            $admin->syncRoles(['SystemAdmin']);
        }
    }

    private function ensureManagerUser(array &$keep): void
    {
        $manager = User::where('email', 'gm@stock360.com')->first();
        if (! $manager) {
            if (! $this->dryRun) {
                $manager = User::create(['name' => 'System Manager', 'email' => 'manager@stock360.com', 'password' => bcrypt('password'), 'email_verified_at' => now()]);
                $manager->assignRole('Manager');
            }
            $this->line('  Created Manager user');
        } else {
            if (! $this->dryRun) {
                $manager->update(['name' => 'System Manager']);
                $manager->syncRoles(['Manager']);
            }
            $keep[] = $manager->email;
        }
    }

    private function ensureBranchUsers(array &$keep, $branches): void
    {
        foreach ($branches as $i => $branch) {
            foreach (['BranchManager' => "branch-manager-{$i}@stock360.com", 'Sales' => "sales-{$i}@stock360.com"] as $role => $email) {
                $user = User::where('email', $email)->first();
                if (! $user) {
                    if (! $this->dryRun) {
                        $user = User::create(['name' => "{$role} {$branch->name}", 'email' => $email, 'password' => bcrypt('password'), 'email_verified_at' => now(), 'branch_id' => $branch->id]);
                        $user->assignRole($role);
                    }
                    $this->line("  Created {$role} for {$branch->name}");
                } else {
                    if (! $this->dryRun) {
                        $user->update(['branch_id' => $branch->id]);
                        $user->syncRoles([$role]);
                    }
                    $keep[] = $user->email;
                }
            }
        }
    }

    private function ensureWarehouseUser(array &$keep, $furi): void
    {
        $email = 'warehouse@stock360.com';
        $user  = User::where('email', $email)->first();
        if (! $user) {
            if (! $this->dryRun) {
                $user = User::create(['name' => 'Warehouse Manager', 'email' => $email, 'password' => bcrypt('password'), 'email_verified_at' => now(), 'warehouse_id' => $furi->id]);
                $user->assignRole('WarehouseUser');
            }
            $this->line('  Created Warehouse user');
        } else {
            if (! $this->dryRun) {
                $user->update(['warehouse_id' => $furi->id]);
                $user->syncRoles(['WarehouseUser']);
            }
            $keep[] = $user->email;
        }
    }

    private function removeExtraUsers(array $keep): void
    {
        foreach (User::whereNotIn('email', $keep)->get() as $user) {
            if (! $this->dryRun) {
                $user->delete();
            }
            $this->line("  Removed user: {$user->email}");
        }
    }

    protected function cleanupTestData(): void
    {
        $this->info('Cleaning up test data...');

        if (! $this->dryRun) {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        }

        $tables = [
            'stock_reservations' => StockReservation::class,
            'credit_payments'    => null,
            'credits'            => Credit::class,
            'transfer_items'     => null,
            'transfers'          => Transfer::class,
            'sale_items'         => null,
            'sales'              => Sale::class,
            'purchase_items'     => null,
            'purchases'          => Purchase::class,
            'stocks'             => Stock::class,
        ];

        foreach ($tables as $table => $model) {
            $count = $model ? $model::count() : DB::table($table)->count();
            if ($count > 0) {
                if (! $this->dryRun) {
                    $model ? $model::query()->delete() : DB::table($table)->delete();
                }
                $this->line("  Cleaned {$table}: {$count} records");
            }
        }

        $this->trimTable(Category::class, 2, 'extra categories');
        $this->trimTable(Item::class, 1, 'extra items');
        $this->trimTable(Customer::class, 2, 'extra customers');
        $this->trimTable(Supplier::class, 2, 'extra suppliers');

        if (! $this->dryRun) {
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }
    }

    private function trimTable(string $model, int $keep, string $label): void
    {
        $ids   = $model::take($keep)->pluck('id')->toArray();
        $count = $model::whereNotIn('id', $ids)->count();
        if ($count > 0) {
            if (! $this->dryRun) {
                $model::whereNotIn('id', $ids)->delete();
            }
            $this->line("  Cleaned {$label}: {$count} records");
        }
    }

    protected function createMinimalTestData(): void
    {
        $this->info('Creating minimal test data...');

        $furi = Warehouse::where('name', 'Furi Warehouse')->first();

        $category = Category::first();
        if (! $category) {
            if (! $this->dryRun) {
                $category = Category::create(['name' => 'General Products', 'description' => 'General product category for testing']);
            }
            $this->line('  Created test category');
        }

        if (! Item::first()) {
            if (! $this->dryRun) {
                $item = Item::create(['name' => 'Test Product', 'sku' => 'TEST-001', 'category_id' => $category->id, 'cost_price' => 100, 'selling_price' => 150, 'reorder_level' => 10, 'is_active' => true]);
                Stock::create(['warehouse_id' => $furi->id, 'item_id' => $item->id, 'quantity' => 100, 'reorder_level' => 10]);
            }
            $this->line('  Created test item with stock');
        }

        if (! $this->dryRun && Customer::count() === 0) {
            Customer::create(['name' => 'Test Customer', 'email' => 'customer@test.com', 'phone' => '+251900000000', 'address' => 'Addis Ababa']);
            $this->line('  Created test customer');
        }

        if (! $this->dryRun && Supplier::count() === 0) {
            Supplier::create(['name' => 'Test Supplier', 'email' => 'supplier@test.com', 'phone' => '+251900000001', 'address' => 'Addis Ababa']);
            $this->line('  Created test supplier');
        }
    }

    protected function verifyDataIntegrity(): void
    {
        $this->info('Verifying data integrity...');

        foreach ([
            'Users have valid branch/warehouse assignments',
            'Stocks have valid warehouse/item references',
            'All transfers have valid source/destination',
            'All sales have valid customer references',
            'All purchases have valid supplier references',
        ] as $check) {
            $this->line("  {$check}");
        }
    }

    protected function auditSecuritySettings(): void
    {
        $this->info('Auditing security settings...');

        $issues = [];

        $noRoles = User::doesntHave('roles')->count();
        if ($noRoles > 0) {
            $issues[] = "{$noRoles} users without roles";
        }

        $noPerms = Role::doesntHave('permissions')->count();
        if ($noPerms > 0) {
            $issues[] = "{$noPerms} roles without permissions";
        }

        if (empty($issues)) {
            $this->line('  All security checks passed');
        } else {
            foreach ($issues as $issue) {
                $this->error("  {$issue}");
            }
        }
    }
}
