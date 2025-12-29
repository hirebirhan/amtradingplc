<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Item;
use App\Models\Sale;
use App\Models\Purchase;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Models\Credit;
use App\Models\Expense;
use App\Models\User;
use App\Enums\UserRole;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReportsController extends Controller
{
    /**
     * Display reports dashboard
     */
    public function index()
    {
        // Get key metrics for dashboard
        $current_date = Carbon::now();
        $last_month = $current_date->copy()->subMonth();
        
        // Apply role-based filtering
        $userFilter = $this->getUserScopeFilter();
        
        // Inventory summary
        $total_items = Item::count();
        $low_stock_count = DB::table('items')
            ->leftJoin('stocks', 'items.id', '=', 'stocks.item_id')
            ->select('items.id', 'items.reorder_level')
            ->selectRaw('COALESCE(SUM(stocks.quantity), 0) as current_stock')
            ->groupBy('items.id', 'items.reorder_level')
            ->havingRaw('COALESCE(SUM(stocks.quantity), 0) <= items.reorder_level')
            ->havingRaw('COALESCE(SUM(stocks.quantity), 0) > 0')
            ->get()->count();
            
        $out_of_stock_count = DB::table('items')
            ->leftJoin('stocks', 'items.id', '=', 'stocks.item_id')
            ->select('items.id')
            ->selectRaw('COALESCE(SUM(stocks.quantity), 0) as current_stock')
            ->groupBy('items.id')
            ->havingRaw('COALESCE(SUM(stocks.quantity), 0) <= 0')
            ->get()->count();
        
        // Sales summary (current month) - with role-based filtering
        $salesQuery = Sale::whereBetween('sale_date', [
            $current_date->startOfMonth()->format('Y-m-d'),
            $current_date->endOfMonth()->format('Y-m-d')
        ]);
        
        // Apply scope filtering to sales
        if (!empty($userFilter['warehouse_ids'])) {
            $salesQuery->where(function($query) use ($userFilter) {
                $query->whereIn('warehouse_id', $userFilter['warehouse_ids'])
                      ->orWhereIn('branch_id', $userFilter['branch_ids']);
            });
        }
        
        $current_month_sales = $userFilter['can_view_revenue'] ? $salesQuery->sum('total_amount') : 0;
        
        // Last month sales with same filtering
        $lastMonthSalesQuery = Sale::whereBetween('sale_date', [
            $last_month->startOfMonth()->format('Y-m-d'),
            $last_month->endOfMonth()->format('Y-m-d')
        ]);
        
        if (!empty($userFilter['warehouse_ids'])) {
            $lastMonthSalesQuery->where(function($query) use ($userFilter) {
                $query->whereIn('warehouse_id', $userFilter['warehouse_ids'])
                      ->orWhereIn('branch_id', $userFilter['branch_ids']);
            });
        }
        
        $last_month_sales = $userFilter['can_view_revenue'] ? $lastMonthSalesQuery->sum('total_amount') : 0;
        
        // Purchase summary (current month) - with role-based filtering  
        $purchaseQuery = Purchase::whereBetween('purchase_date', [
            $current_date->copy()->startOfMonth()->format('Y-m-d'),
            $current_date->copy()->endOfMonth()->format('Y-m-d')
        ]);
        
        if (!empty($userFilter['warehouse_ids'])) {
            $purchaseQuery->whereIn('warehouse_id', $userFilter['warehouse_ids']);
        }
        
        $current_month_purchases = $userFilter['can_view_revenue'] ? $purchaseQuery->sum('total_amount') : 0;
        
        // Last month purchases with same filtering
        $lastMonthPurchaseQuery = Purchase::whereBetween('purchase_date', [
            $last_month->copy()->startOfMonth()->format('Y-m-d'),
            $last_month->copy()->endOfMonth()->format('Y-m-d')
        ]);
        
        if (!empty($userFilter['warehouse_ids'])) {
            $lastMonthPurchaseQuery->whereIn('warehouse_id', $userFilter['warehouse_ids']);
        }
        
        $last_month_purchases = $userFilter['can_view_revenue'] ? $lastMonthPurchaseQuery->sum('total_amount') : 0;
        
        // Recent activities - with role-based filtering
        $recentSalesQuery = Sale::with(['customer', 'user'])
            ->latest()
            ->limit(5);
            
        if (!empty($userFilter['warehouse_ids'])) {
            $recentSalesQuery->where(function($query) use ($userFilter) {
                $query->whereIn('warehouse_id', $userFilter['warehouse_ids'])
                      ->orWhereIn('branch_id', $userFilter['branch_ids']);
            });
        }
        
        $recent_sales = $recentSalesQuery->get();
            
        $recentPurchasesQuery = Purchase::with(['supplier', 'user'])
            ->latest()
            ->limit(5);
            
        if (!empty($userFilter['warehouse_ids'])) {
            $recentPurchasesQuery->whereIn('warehouse_id', $userFilter['warehouse_ids']);
        }
        
        $recent_purchases = $recentPurchasesQuery->get();
        
        return view('reports.index', compact(
            'total_items',
            'low_stock_count',
            'out_of_stock_count',
            'current_month_sales',
            'last_month_sales',
            'current_month_purchases',
            'last_month_purchases',
            'recent_sales',
            'recent_purchases'
        ));
    }

    /**
     * Display inventory reports
     */
    public function inventory(Request $request)
    {
        // Apply role-based filtering
        $userFilter = $this->getUserScopeFilter();
        
        // Get filters
        $warehouse_id = $request->warehouse_id;
        $category_id = $request->category_id;
        $stock_status = $request->stock_status;

        // Base query for items with their current stock - with role-based filtering
        $query = Item::with(['category', 'stocks'])
            ->select('items.*')
            ->selectRaw('COALESCE(SUM(stocks.quantity), 0) as current_stock')
            ->leftJoin('stocks', 'items.id', '=', 'stocks.item_id')
            ->when($warehouse_id, function($query) use ($warehouse_id) {
                return $query->where('stocks.warehouse_id', $warehouse_id);
            })
            ->when($category_id, function($query) use ($category_id) {
                return $query->where('items.category_id', $category_id);
            });
            
        // Apply user scope filtering to inventory
        if (!empty($userFilter['warehouse_ids'])) {
            $query->whereIn('stocks.warehouse_id', $userFilter['warehouse_ids']);
        }
        
        $query->groupBy('items.id', 'items.reorder_level', 'items.name', 'items.sku', 'items.barcode', 'items.description', 'items.category_id', 'items.cost_price', 'items.selling_price', 'items.unit', 'items.brand', 'items.status', 'items.is_active', 'items.created_at', 'items.updated_at', 'items.deleted_at');

        // Apply stock status filter
        if ($stock_status) {
            $query->having('current_stock', $stock_status === 'in_stock' ? '>' : '<=', DB::raw('items.reorder_level'))
                ->when($stock_status === 'out_of_stock', function($query) {
                    return $query->having('current_stock', '<=', 0);
                });
        }

        // Get inventory items
        $inventory_items = $query->get();

        // Calculate statistics
        $total_items = $inventory_items->count();
        $in_stock_items = $inventory_items->filter(function($item) {
            return $item->current_stock > $item->reorder_level;
        })->count();
        $low_stock_items = $inventory_items->filter(function($item) {
            return $item->current_stock > 0 && $item->current_stock <= $item->reorder_level;
        })->count();
        $out_of_stock_items = $inventory_items->filter(function($item) {
            return $item->current_stock <= 0;
        })->count();

        // Get low stock alerts with warehouse information - with role-based filtering
        $lowStockQuery = Item::with(['category', 'stocks.warehouse'])
            ->select('items.*')
            ->selectRaw('COALESCE(SUM(stocks.quantity), 0) as current_stock')
            ->leftJoin('stocks', 'items.id', '=', 'stocks.item_id')
            ->having('current_stock', '>', 0)
            ->having('current_stock', '<=', DB::raw('items.reorder_level'))
            ->when($warehouse_id, function($query) use ($warehouse_id) {
                return $query->where('stocks.warehouse_id', $warehouse_id);
            });
            
        // Apply user scope filtering to low stock alerts
        if (!empty($userFilter['warehouse_ids'])) {
            $lowStockQuery->whereIn('stocks.warehouse_id', $userFilter['warehouse_ids']);
        }
        
        $low_stock_alerts = $lowStockQuery
            ->groupBy('items.id', 'items.reorder_level', 'items.name', 'items.sku', 'items.barcode', 'items.description', 'items.category_id', 'items.cost_price', 'items.selling_price', 'items.unit', 'items.brand', 'items.status', 'items.is_active', 'items.created_at', 'items.updated_at', 'items.deleted_at')
            ->limit(10)
            ->get();

        // Calculate category distribution with actual stock counts - with role-based filtering
        $categoryQuery = Item::with('category')
            ->select('categories.name', DB::raw('COUNT(DISTINCT items.id) as count'))
            ->leftJoin('categories', 'items.category_id', '=', 'categories.id')
            ->leftJoin('stocks', 'items.id', '=', 'stocks.item_id')
            ->when($warehouse_id, function($query) use ($warehouse_id) {
                return $query->where('stocks.warehouse_id', $warehouse_id);
            });
            
        // Apply user scope filtering to category distribution
        if (!empty($userFilter['warehouse_ids'])) {
            $categoryQuery->whereIn('stocks.warehouse_id', $userFilter['warehouse_ids']);
        }
        
        $category_distribution = $categoryQuery
            ->groupBy('categories.name')
            ->whereNotNull('categories.name')
            ->get()
            ->pluck('count', 'name');

        // Get warehouses and categories for filters - filtered by user scope
        $warehouses = empty($userFilter['warehouse_ids']) ? 
            Warehouse::all() : 
            Warehouse::whereIn('id', $userFilter['warehouse_ids'])->get();
            
        // Apply branch filtering to categories
        $user = auth()->user();
        if ($user->isSuperAdmin() || $user->isGeneralManager()) {
            $categories = \App\Models\Category::all();
        } else {
            $categories = \App\Models\Category::forBranch($user->branch_id)->get();
        }

        return view('reports.inventory', compact(
            'inventory_items',
            'total_items',
            'in_stock_items',
            'low_stock_items',
            'out_of_stock_items',
            'low_stock_alerts',
            'category_distribution',
            'warehouses',
            'categories',
            'warehouse_id',
            'category_id',
            'stock_status'
        ));
    }
    
    /**
     * Display sales reports
     */
    public function sales(Request $request)
    {
        // Apply role-based filtering
        $userFilter = $this->getUserScopeFilter();
        
        // For Sales users, deny revenue access
        if (!$userFilter['can_view_revenue']) {
            abort(403, 'Access denied. Sales users cannot view revenue reports.');
        }
        
        // Get filters
        $date_from = $request->date_from ?? Carbon::now()->subDays(30)->format('Y-m-d');
        $date_to = $request->date_to ?? Carbon::now()->format('Y-m-d');
        $customer_id = $request->customer_id;
        $warehouse_id = $request->warehouse_id;
        
        // Query sales with role-based filtering
        $salesQuery = Sale::with(['customer', 'saleItems.item', 'warehouse', 'user', 'branch'])
            ->whereBetween('sale_date', [$date_from, $date_to])
            ->when($customer_id, function($query) use ($customer_id) {
                return $query->where('customer_id', $customer_id);
            })
            ->when($warehouse_id, function($query) use ($warehouse_id) {
                return $query->where('warehouse_id', $warehouse_id);
            });
            
        // Apply user scope filtering
        if (!empty($userFilter['warehouse_ids'])) {
            $salesQuery->where(function($query) use ($userFilter) {
                $query->whereIn('warehouse_id', $userFilter['warehouse_ids'])
                      ->orWhereIn('branch_id', $userFilter['branch_ids']);
            });
        }
        
        $sales = $salesQuery->latest()->get();
            
        // Calculate summary statistics
        $total_sales = $sales->sum('total_amount');
        $sales_count = $sales->count();
        $total_items_sold = $sales->sum(function($sale) {
            return $sale->saleItems->sum('quantity');
        });
        
        // Top selling items with role-based filtering
        $topItemsQuery = DB::table('sale_items')
            ->join('items', 'sale_items.item_id', '=', 'items.id')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->whereBetween('sales.sale_date', [$date_from, $date_to]);
            
        // Apply user scope filtering to top items
        if (!empty($userFilter['warehouse_ids'])) {
            $topItemsQuery->where(function($query) use ($userFilter) {
                $query->whereIn('sales.warehouse_id', $userFilter['warehouse_ids'])
                      ->orWhereIn('sales.branch_id', $userFilter['branch_ids']);
            });
        }
        
        $top_items = $topItemsQuery
            ->select('items.name', DB::raw('SUM(sale_items.quantity) as total_quantity'))
            ->groupBy('items.name')
            ->orderBy('total_quantity', 'desc')
            ->limit(10)
            ->get();
            
        // Payment methods distribution for chart
        $payment_methods = $sales->groupBy('payment_method')
            ->map(function($group) {
                return $group->count();
            })
            ->toArray();
            
        // Get data for filters - filtered by user scope
        $customers = Customer::all();
        $warehouses = empty($userFilter['warehouse_ids']) ? 
            Warehouse::all() : 
            Warehouse::whereIn('id', $userFilter['warehouse_ids'])->get();
        
        return view('reports.sales', compact(
            'sales', 
            'total_sales', 
            'sales_count', 
            'total_items_sold', 
            'top_items', 
            'payment_methods',
            'customers', 
            'warehouses',
            'date_from',
            'date_to'
        ));
    }
    
    /**
     * Display purchase reports
     */
    public function purchases(Request $request)
    {
        // Apply role-based filtering
        $userFilter = $this->getUserScopeFilter();
        
        // Check if user has purchase view permission
        if (!auth()->user()->can('purchases.view')) {
            abort(403, 'Access denied. You do not have permission to view purchase reports.');
        }
        
        // Get filters
        $date_from = $request->date_from ?? Carbon::now()->subDays(30)->format('Y-m-d');
        $date_to = $request->date_to ?? Carbon::now()->format('Y-m-d');
        $supplier_id = $request->supplier_id;
        $warehouse_id = $request->warehouse_id;
        
        // Query purchases with role-based filtering
        $purchasesQuery = Purchase::with(['supplier', 'purchaseItems.item', 'warehouse', 'user'])
            ->whereBetween('purchase_date', [$date_from, $date_to])
            ->when($supplier_id, function($query) use ($supplier_id) {
                return $query->where('supplier_id', $supplier_id);
            })
            ->when($warehouse_id, function($query) use ($warehouse_id) {
                return $query->where('warehouse_id', $warehouse_id);
            });
            
        // Apply user scope filtering
        if (!empty($userFilter['warehouse_ids'])) {
            $purchasesQuery->whereIn('warehouse_id', $userFilter['warehouse_ids']);
        }
        
        $purchases = $purchasesQuery->latest()->get();
            
        // Calculate summary statistics
        $total_purchases = $purchases->sum('total_amount');
        $purchases_count = $purchases->count();
        $total_items_purchased = $purchases->sum(function($purchase) {
            return $purchase->purchaseItems->sum('quantity');
        });
        
        // Top purchased items with role-based filtering
        $topItemsQuery = DB::table('purchase_items')
            ->join('items', 'purchase_items.item_id', '=', 'items.id')
            ->join('purchases', 'purchase_items.purchase_id', '=', 'purchases.id')
            ->whereBetween('purchases.purchase_date', [$date_from, $date_to]);
            
        // Apply user scope filtering to top purchased items
        if (!empty($userFilter['warehouse_ids'])) {
            $topItemsQuery->whereIn('purchases.warehouse_id', $userFilter['warehouse_ids']);
        }
        
        $top_items = $topItemsQuery
            ->select('items.name', DB::raw('SUM(purchase_items.quantity) as total_quantity'))
            ->groupBy('items.name')
            ->orderBy('total_quantity', 'desc')
            ->limit(10)
            ->get();
            
        // Get data for filters - filtered by user scope
        $suppliers = Supplier::all();
        $warehouses = empty($userFilter['warehouse_ids']) ? 
            Warehouse::all() : 
            Warehouse::whereIn('id', $userFilter['warehouse_ids'])->get();
        
        return view('reports.purchases', compact(
            'purchases', 
            'total_purchases', 
            'purchases_count', 
            'total_items_purchased', 
            'top_items', 
            'suppliers', 
            'warehouses',
            'date_from',
            'date_to'
        ));
    }
    
    /**
     * Display financial reports
     */
    public function financial(Request $request)
    {
        // Apply role-based filtering
        $userFilter = $this->getUserScopeFilter();
        
        // Check if user has appropriate permissions for financial data
        if (!auth()->user()->can('reports.view') || (!auth()->user()->can('purchases.view') && !auth()->user()->hasRole([UserRole::SUPER_ADMIN->value, UserRole::MANAGER->value, UserRole::BRANCH_MANAGER->value]))) {
            abort(403, 'Access denied. You do not have permission to view complete financial reports.');
        }
        
        // Get filters
        $date_from = $request->date_from ?? Carbon::now()->subDays(30)->format('Y-m-d');
        $date_to = $request->date_to ?? Carbon::now()->format('Y-m-d');
        $warehouse_id = $request->warehouse_id;
        
        // Total sales with scope filtering
        $salesQuery = Sale::whereBetween('sale_date', [$date_from, $date_to])
            ->when($warehouse_id, function($query) use ($warehouse_id) {
                return $query->where('warehouse_id', $warehouse_id);
            });
            
        if (!empty($userFilter['warehouse_ids'])) {
            $salesQuery->where(function($query) use ($userFilter) {
                $query->whereIn('warehouse_id', $userFilter['warehouse_ids'])
                      ->orWhereIn('branch_id', $userFilter['branch_ids']);
            });
        }
        
        $sales = $salesQuery->sum('total_amount');
            
        // Total purchases with scope filtering
        $purchasesQuery = Purchase::whereBetween('purchase_date', [$date_from, $date_to])
            ->when($warehouse_id, function($query) use ($warehouse_id) {
                return $query->where('warehouse_id', $warehouse_id);
            });
            
        if (!empty($userFilter['warehouse_ids'])) {
            $purchasesQuery->whereIn('warehouse_id', $userFilter['warehouse_ids']);
        }
        
        $purchases = $purchasesQuery->sum('total_amount');
            
        // Total expenses with scope filtering
        $expensesQuery = Expense::whereBetween('expense_date', [$date_from, $date_to]);
            
        // Apply branch filtering for expenses (expenses are linked to branches, not warehouses)
        if (!empty($userFilter['branch_ids'])) {
            $expensesQuery->whereIn('branch_id', $userFilter['branch_ids']);
        }
        
        $expenses = $expensesQuery->sum('amount');
            
        // Profit = Sales - (Purchases + Expenses)
        $profit = $sales - ($purchases + $expenses);
        
        // Outstanding credits with scope filtering
        $creditsQuery = Credit::whereIn('status', ['active', 'partially_paid', 'overdue'])
            ->when($warehouse_id, function($query) use ($warehouse_id) {
                return $query->where('warehouse_id', $warehouse_id);
            });
            
        // Apply user scope filtering for credits
        if (!empty($userFilter['warehouse_ids'])) {
            $creditsQuery->where(function($query) use ($userFilter) {
                $query->whereIn('warehouse_id', $userFilter['warehouse_ids'])
                      ->orWhereIn('branch_id', $userFilter['branch_ids']);
            });
        }
        
        $outstanding_credits = $creditsQuery->sum('balance');
        
        // Monthly data for charts
        $monthly_sales = DB::table('sales')
            ->when($warehouse_id, function($query) use ($warehouse_id) {
                return $query->where('warehouse_id', $warehouse_id);
            })
            ->whereBetween('sale_date', [Carbon::parse($date_from)->startOfMonth(), Carbon::parse($date_to)->endOfMonth()])
            ->select(DB::raw('YEAR(sale_date) as year, MONTH(sale_date) as month, SUM(total_amount) as total'))
            ->groupBy('year', 'month')
            ->orderBy('year')
            ->orderBy('month')
            ->get();
            
        $monthly_purchases = DB::table('purchases')
            ->when($warehouse_id, function($query) use ($warehouse_id) {
                return $query->where('warehouse_id', $warehouse_id);
            })
            ->whereBetween('purchase_date', [Carbon::parse($date_from)->startOfMonth(), Carbon::parse($date_to)->endOfMonth()])
            ->select(DB::raw('YEAR(purchase_date) as year, MONTH(purchase_date) as month, SUM(total_amount) as total'))
            ->groupBy('year', 'month')
            ->orderBy('year')
            ->orderBy('month')
            ->get();
            
        $monthly_expenses = DB::table('expenses')
            ->whereBetween('expense_date', [Carbon::parse($date_from)->startOfMonth(), Carbon::parse($date_to)->endOfMonth()])
            ->when(!empty($userFilter['branch_ids']), function($query) use ($userFilter) {
                return $query->whereIn('branch_id', $userFilter['branch_ids']);
            })
            ->select(DB::raw('YEAR(expense_date) as year, MONTH(expense_date) as month, SUM(amount) as total'))
            ->groupBy('year', 'month')
            ->orderBy('year')
            ->orderBy('month')
            ->get();
            
        // Warehouses for filter
        $warehouses = Warehouse::all();
        
        return view('reports.financial', compact(
            'sales',
            'purchases',
            'expenses',
            'profit',
            'outstanding_credits',
            'monthly_sales',
            'monthly_purchases',
            'monthly_expenses',
            'warehouses',
            'date_from',
            'date_to'
        ));
    }
    
    /**
     * Display activity logs
     */
    public function activity(Request $request)
    {
        // Apply role-based filtering
        $userFilter = $this->getUserScopeFilter();
        
        // Here we'll use the spatie/laravel-activitylog package if it's installed
        // Otherwise, we'll implement a simplified version
        
        $date_from = $request->date_from ?? Carbon::now()->subDays(7)->format('Y-m-d');
        $date_to = $request->date_to ?? Carbon::now()->format('Y-m-d');
        $user_id = $request->user_id;
        $per_page = $request->per_page ?? 10; // Allow customizable per page count
        
        // Validate per_page input
        $per_page = in_array($per_page, [10, 15, 25, 50, 100]) ? $per_page : 10;
        
        // Check if the activity log package is available
        if (class_exists(\Spatie\Activitylog\Models\Activity::class)) {
            $activitiesQuery = \Spatie\Activitylog\Models\Activity::query()
                ->with(['causer' => function($query) {
                    // Only load non-deleted users to avoid orphan data
                    $query->whereNull('deleted_at');
                }])
                ->whereBetween('created_at', [$date_from . ' 00:00:00', $date_to . ' 23:59:59'])
                ->when($user_id, function($query) use ($user_id) {
                    return $query->where('causer_id', $user_id);
                })
                // Filter out activities from deleted users to prevent orphan data
                ->whereHas('causer', function($query) {
                    $query->whereNull('deleted_at');
                });
                
            // Apply user scope filtering for Sales users (only see their own activities)
            if ($userFilter['scope_type'] === 'warehouse' || $userFilter['scope_type'] === 'branch') {
                $activitiesQuery->where('causer_id', auth()->id());
            }
            
            $activities = $activitiesQuery->latest()->paginate($per_page);
        } else {
            // Simplified alternative using users table (recent users only)
            $activitiesQuery = User::when($user_id, function($query) use ($user_id) {
                    return $query->where('id', $user_id);
                })
                ->whereBetween('updated_at', [$date_from . ' 00:00:00', $date_to . ' 23:59:59'])
                ->whereNull('deleted_at') // Exclude deleted users
                ->select('id', 'name', 'email', 'updated_at');
                
            // Apply user scope filtering
            if ($userFilter['scope_type'] === 'warehouse' || $userFilter['scope_type'] === 'branch') {
                $activitiesQuery->where('id', auth()->id());
            }
            
            $activities = $activitiesQuery->latest('updated_at')->paginate($per_page);
        }
        
        // Users for filter - limited based on scope and exclude deleted users
        if ($userFilter['scope_type'] === 'all') {
            $users = User::whereNull('deleted_at')->orderBy('name')->get();
        } else {
            // Only show current user for limited scope users
            $users = User::where('id', auth()->id())->whereNull('deleted_at')->get();
        }
        
        return view('reports.activity', compact('activities', 'users', 'date_from', 'date_to'));
    }

    /**
     * Get user scope filter based on role and assignments
     */
    private function getUserScopeFilter(): array
    {
        $user = auth()->user();
        
        return [
            'warehouse_ids' => $this->getUserWarehouseIds($user),
            'branch_ids' => $this->getUserBranchIds($user),
            'can_view_revenue' => $this->canViewRevenue($user),
            'scope_type' => $this->getScopeType($user)
        ];
    }

    /**
     * Get warehouse IDs user can access
     */
    private function getUserWarehouseIds(User $user): array
    {
        if ($user->hasRole([UserRole::SYSTEM_ADMIN->value, UserRole::MANAGER->value])) {
            return Warehouse::pluck('id')->toArray();
        }
        
        if ($user->warehouse_id) {
            return [$user->warehouse_id];
        }
        
        if ($user->branch_id && $user->hasRole(UserRole::BRANCH_MANAGER->value)) {
            // Branch managers access warehouses through their branch
            return Warehouse::whereHas('branches', function($query) use ($user) {
                $query->where('branches.id', $user->branch_id);
            })->pluck('id')->toArray();
        }
        
        return [];
    }

    /**
     * Get branch IDs user can access
     */
    private function getUserBranchIds(User $user): array
    {
        if ($user->hasRole([UserRole::SYSTEM_ADMIN->value, UserRole::MANAGER->value])) {
            return DB::table('branches')->pluck('id')->toArray();
        }
        
        if ($user->branch_id) {
            return [$user->branch_id];
        }
        
        return [];
    }

    /**
     * Check if user can view revenue data
     */
    private function canViewRevenue(User $user): bool
    {
        return !$user->hasRole(UserRole::SALES->value);
    }

    /**
     * Get user's scope type
     */
    private function getScopeType(User $user): string
    {
        if ($user->hasRole([UserRole::SYSTEM_ADMIN->value, UserRole::MANAGER->value])) {
            return 'all';
        }
        
        if ($user->warehouse_id) {
            return 'warehouse';
        }
        
        if ($user->branch_id) {
            return 'branch';
        }
        
        return 'none';
    }
}