<?php

use App\Http\Controllers\Auth\LoginController;
use App\Livewire\Categories\Create as CategoryCreate;
use App\Livewire\Categories\Edit as CategoryEdit;
use App\Livewire\Categories\Index as CategoryIndex;
use App\Livewire\Categories\Show as CategoryShow;
use App\Livewire\Items\Create as ItemCreate;
use App\Livewire\Items\Edit as ItemEdit;
use App\Livewire\Items\Index as ItemIndex;
use App\Livewire\Items\Show as ItemShow;
use App\Models\Category;
use App\Models\Item;
use App\Models\Branch;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PriceHistoryController;
use App\Http\Controllers\ReportsController;
use Illuminate\Support\Facades\Auth;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Home and Dashboard
Route::get('/', function () {
    if (Auth::check()) {
        if (!Auth::user()->is_active) {
            Auth::logout();
            return redirect()->route('login')->withErrors([
                'email' => 'Your account has been deactivated. Please contact the administrator.',
            ]);
        }
        return redirect()->route('admin.dashboard');
    }
    return redirect()->route('login');
});

// Authentication Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
});

Route::post('/logout', [LoginController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');



// Protected routes that require authentication and active account
Route::prefix('admin')->middleware(['auth', 'active'])->group(function () {
    Route::get('/dashboard', [App\Http\Controllers\Admin\DashboardController::class, 'index'])->name('admin.dashboard');
    Route::get('/dashboard/chart-data/{range}', [App\Http\Controllers\Admin\DashboardController::class, 'getChartData'])->name('admin.dashboard.chart-data');

    // Profile Routes
    Route::get('/profile/edit', App\Livewire\Profile\Edit::class)->name('admin.profile.edit');

    // Category Routes
    Route::prefix('categories')->name('admin.categories.')->group(function () {
        Route::get('/', function() {
            return view('categories');
        })->middleware('permission:categories.view')->name('index');

        Route::get('/create', function() {
            return view('categories-create');
        })->middleware('permission:categories.create')->name('create');

        Route::get('/{category}', function (Category $category) {
            return view('categories-show', ['category' => $category]);
        })->middleware('permission:categories.view')->name('show');

        Route::get('/{category}/edit', function (Category $category) {
            return view('categories-edit', ['category' => $category]);
        })->middleware('permission:categories.edit')->name('edit');
    });

    // Item Routes
    Route::prefix('items')->name('admin.items.')->middleware('App\Http\Middleware\EnforceBranchAuthorization')->group(function () {
        Route::get('/', function() {
            return view('items');
        })->middleware('permission:items.view')->name('index');

        Route::get('/create', function() {
            return view('items-create');
        })->middleware('permission:items.create')->name('create');

        // Add new route for template download
        Route::get('/import-template/download', [App\Http\Controllers\ItemImportController::class, 'downloadTemplate'])
            ->middleware('permission:items.create')
            ->name('import-template.download');

        // Add import page route (best practice)
        Route::get('/import', App\Livewire\Admin\Items\ImportItems::class)
            ->middleware('permission:items.create')->name('import');

        // Import handlers - These are now handled by the Livewire component
        // Route::post('/import/preview', [App\Http\Controllers\ItemImportController::class, 'preview'])
        //     ->middleware('permission:items.create')
        //     ->name('import.preview');
        // Route::post('/import/apply', [App\Http\Controllers\ItemImportController::class, 'apply'])
        //     ->middleware('permission:items.create')
        //     ->name('import.apply');

        Route::get('/{item}', function(Item $item) {
            return view('items-show', ['item' => $item]);
        })->middleware('permission:items.view')->name('show');

        Route::get('/{item}/edit', function(Item $item) {
            return view('items-edit', ['item' => $item]);
        })->middleware('permission:items.edit')->name('edit');
    });

    // Warehouse routes
    Route::prefix('warehouses')->name('admin.warehouses.')->middleware('App\Http\Middleware\EnforceBranchAuthorization')->group(function () {
        Route::get('/', App\Livewire\Warehouses\Index::class)
            ->middleware('permission:warehouses.view')
            ->name('index');

        Route::get('/create', App\Livewire\Warehouses\Create::class)
            ->middleware('permission:warehouses.create')
            ->name('create');

        Route::get('/{warehouse}', App\Livewire\Warehouses\Show::class)
            ->middleware('permission:warehouses.view')
            ->name('show');

        Route::get('/{warehouse}/edit', App\Livewire\Warehouses\Edit::class)
            ->middleware('permission:warehouses.edit')
            ->name('edit');
    });

    // Branch Routes
    Route::prefix('branches')->name('admin.branches.')->group(function () {
        Route::get('/', function () {
            return view('branches');
        })->middleware('permission:branches.view')->name('index');

        Route::get('/create', function () {
            return view('branches-create');
        })->middleware('permission:branches.create')->name('create');

        Route::get('/{branch}', function (Branch $branch) {
            return view('branches-show', ['branch' => $branch]);
        })->middleware('permission:branches.view')->name('show');

        Route::get('/{branch}/edit', function (Branch $branch) {
            return view('branches-edit', ['branch' => $branch]);
        })->middleware('permission:branches.edit')->name('edit');
    });

    // User Management Routes - SuperAdmin and BranchManager only
    Route::prefix('users')->name('admin.users.')->group(function () {
        Route::get('/', function() {
            return view('users');
        })->middleware('permission:users.view')->name('index');

        Route::get('/create', function() {
            return view('users-create');
        })->middleware('permission:users.create')->name('create');

        Route::get('/{user}', function(App\Models\User $user) {
            return view('users-show', ['user' => $user]);
        })->middleware('permission:users.view')->name('show');

        Route::get('/{user}/edit', function(App\Models\User $user) {
            return view('users-edit', ['user' => $user]);
        })->middleware('permission:users.edit')->name('edit');
    });

    // Employee Management Routes
    Route::prefix('employees')->name('admin.employees.')->group(function () {
        Route::get('/', function() {
            return view('employees');
        })->middleware('permission:employees.view')
        ->name('index');

        Route::get('/create', function() {
            return view('employees-create');
        })->middleware('permission:employees.create')
        ->name('create');

        Route::get('/{employee}', function(App\Models\Employee $employee) {
            return view('employees-show', ['employee' => $employee]);
        })->middleware('permission:employees.view')
        ->name('show');

        Route::get('/{employee}/edit', function(App\Models\Employee $employee) {
            return view('employees-edit', ['employee' => $employee]);
        })->middleware('permission:employees.edit')
        ->name('edit');
    });

    // Role Management Routes - SuperAdmin only
    Route::prefix('roles')->name('admin.roles.')->group(function () {
        Route::get('/', function() {
            return view('roles');
        })->middleware('permission:roles.view')->name('index');
        
        Route::get('/create', function() {
            return view('roles/create');
        })->middleware('permission:roles.create')->name('create');
        
        Route::get('/permissions', function() {
            return view('roles-permissions');
        })->middleware('permission:roles.view')->name('permissions');
            
        Route::get('/user-assignments', function() {
            return view('roles-user-assignments');
        })->middleware('permission:roles.view')->name('user-assignments');
    });

    // Purchase Routes
    Route::prefix('purchases')->name('admin.purchases.')->middleware('App\Http\Middleware\EnforceBranchAuthorization')->group(function () {
        Route::get('/', function() {
            return view('purchases');
        })->middleware('permission:purchases.view')->name('index');

        Route::get('/create', function() {
            return view('purchases-create');
        })->middleware('permission:purchases.create')->name('create');

        Route::get('/{purchase}', function(App\Models\Purchase $purchase) {
            return view('purchases-show', ['purchase' => $purchase]);
        })->middleware('permission:purchases.view')->name('show');


        
        Route::get('/{purchase}/pdf', [App\Http\Controllers\PurchasesController::class, 'generatePdf'])
            ->middleware('permission:purchases.view')
            ->name('pdf');
            
        Route::get('/{purchase}/print', [App\Http\Controllers\PurchasesController::class, 'printPurchase'])
            ->middleware('permission:purchases.view')
            ->name('print');
    });

    // Sales Routes
    Route::prefix('sales')->name('admin.sales.')->middleware('App\Http\Middleware\EnforceBranchAuthorization')->group(function () {
        Route::get('/', App\Livewire\Sales\Index::class)
            ->middleware('permission:sales.view')->name('index');

        Route::get('/create', App\Livewire\Sales\Create::class)
            ->middleware('permission:sales.create')
            ->name('create');

        Route::get('/{sale}', App\Livewire\Sales\Show::class)
            ->middleware('permission:sales.view')->name('show');

        Route::get('/{sale}/edit', function(App\Models\Sale $sale) {
            return view('sales-edit', ['sale' => $sale]);
        })->middleware('permission:sales.edit')->name('edit');
            
        Route::get('/{sale}/print', [App\Http\Controllers\SaleController::class, 'print'])
            ->middleware('permission:sales.view')->name('print');
    });

    // Transfer Routes
    Route::prefix('transfers')->name('admin.transfers.')->middleware('App\Http\Middleware\EnforceBranchAuthorization')->group(function () {
        Route::get('/', App\Livewire\Transfers\Index::class)
            ->middleware('permission:transfers.view')->name('index');

        Route::get('/pending', App\Livewire\Transfers\Pending::class)
            ->middleware('permission:transfers.view')->name('pending');

        Route::get('/create', App\Livewire\Transfers\Create::class)
            ->middleware('permission:transfers.create')->name('create');

        Route::get('/{transfer}', App\Livewire\Transfers\Show::class)
            ->middleware('permission:transfers.view')->name('show');

        Route::get('/{transfer}/edit', function(App\Models\Transfer $transfer) {
            return view('transfers-edit', ['transfer' => $transfer]);
        })->middleware('permission:transfers.edit')->name('edit');
        
        Route::get('/{transfer}/print', [App\Http\Controllers\TransferController::class, 'print'])
            ->middleware('permission:transfers.view')->name('print');
    });

    // Stock Reservations Routes
    Route::prefix('stock-reservations')->name('admin.stock-reservations.')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\StockReservationController::class, 'index'])
            ->middleware('permission:transfers.view')->name('index');

        Route::post('/cleanup', [App\Http\Controllers\Admin\StockReservationController::class, 'cleanup'])
            ->middleware('permission:transfers.edit')->name('cleanup');

        Route::get('/{reservation}', [App\Http\Controllers\Admin\StockReservationController::class, 'show'])
            ->middleware('permission:transfers.view')->name('show');

        Route::post('/{reservation}/release', [App\Http\Controllers\Admin\StockReservationController::class, 'release'])
            ->middleware('permission:transfers.edit')->name('release');

        Route::patch('/{reservation}/extend', [App\Http\Controllers\Admin\StockReservationController::class, 'extend'])
            ->middleware('permission:transfers.edit')->name('extend');

        // API Routes for frontend integration
        Route::get('/api/for-item', [App\Http\Controllers\Admin\StockReservationController::class, 'forItem'])
            ->middleware('permission:transfers.view')->name('api.for-item');
    });

    // Stock Reports Routes
    Route::prefix('stock-reports')->name('admin.stock-reports.')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\StockReportController::class, 'index'])
            ->middleware('permission:items.view')->name('index');

        Route::get('/api/generate', [App\Http\Controllers\Admin\StockReportController::class, 'generateReport'])
            ->middleware('permission:items.view')->name('api.generate');

        Route::get('/api/export', [App\Http\Controllers\Admin\StockReportController::class, 'exportReport'])
            ->middleware('permission:items.view')->name('api.export');
    });

    // Return Routes
    Route::prefix('returns')->name('admin.returns.')->group(function () {
        Route::get('/', function() {
            return view('returns');
        })->middleware('permission:returns.view')->name('index');

        Route::get('/create', function() {
            return view('returns-create');
        })->middleware('permission:returns.create')->name('create');

        Route::get('/{return}', function(App\Models\ReturnModel $return) {
            return view('returns-show', ['return' => $return]);
        })->middleware('permission:returns.view')->name('show');

        Route::get('/{return}/edit', function(App\Models\ReturnModel $return) {
            return view('returns-edit', ['return' => $return]);
        })->middleware('permission:returns.edit')->name('edit');
    });

    // Customer Routes
    Route::prefix('customers')->name('admin.customers.')->group(function () {
        Route::get('/', function() {
            return view('customers');
        })->middleware('permission:customers.view')->name('index');

        Route::get('/create', function() {
            return view('customers-create');
        })->middleware('permission:customers.create')->name('create');

        Route::get('/{customer}', function(App\Models\Customer $customer) {
            return view('customers-show', ['customer' => $customer]);
        })->middleware('permission:customers.view')->name('show');

        Route::get('/{customer}/edit', function(App\Models\Customer $customer) {
            return view('customers-edit', ['customer' => $customer]);
        })->middleware('permission:customers.edit')->name('edit');
    });

    // Supplier Routes
    Route::prefix('suppliers')->name('admin.suppliers.')->group(function () {
        Route::get('/', function() {
            return view('suppliers');
        })->middleware('permission:suppliers.view')->name('index');

        Route::get('/create', function() {
            return view('suppliers-create');
        })->middleware('permission:suppliers.create')->name('create');

        Route::get('/{supplier}', function(App\Models\Supplier $supplier) {
            return view('suppliers-show', ['supplier' => $supplier]);
        })->middleware('permission:suppliers.view')->name('show');

        Route::get('/{supplier}/edit', function(App\Models\Supplier $supplier) {
            return view('suppliers-edit', ['supplier' => $supplier]);
        })->middleware('permission:suppliers.edit')->name('edit');
    });

    // Credit Routes
    Route::prefix('credits')->name('admin.credits.')->group(function () {
        Route::get('/', App\Livewire\Credits\Index::class)
            ->middleware('permission:credits.view')->name('index');

        Route::get('/create', App\Livewire\Credits\Create::class)
            ->middleware('permission:credits.create')->name('create');

        Route::get('/{credit}', App\Livewire\Credits\Show::class)
            ->middleware('permission:credits.view')->name('show');

        Route::get('/{credit}/edit', App\Livewire\Credits\Edit::class)
            ->middleware('permission:credits.edit')->name('edit');
            
        // Credit Payments Routes
        Route::get('/{credit}/payments', App\Livewire\CreditPayment\Index::class)
            ->middleware('permission:credits.view')->name('payments.index');
        
        Route::get('/{credit}/payments/create', App\Livewire\CreditPayment\Create::class)
            ->middleware('permission:credits.edit')->name('payments.create');
            
        Route::post('/{credit}/payments', [App\Http\Controllers\CreditPaymentController::class, 'store'])
            ->middleware('permission:credits.edit')->name('payments.store');
    });

    // Price History Routes
    Route::prefix('price-history')->name('admin.price-history.')->group(function () {
        Route::get('/', [PriceHistoryController::class, 'index'])->name('index');
        Route::get('/{item}', [PriceHistoryController::class, 'show'])->name('show');
    });



    // Bank Accounts Routes
    Route::prefix('bank-accounts')->name('admin.bank-accounts.')->group(function () {
        Route::get('/', App\Livewire\BankAccounts\Index::class)
            ->middleware('permission:bank-accounts.view')
            ->name('index');

        Route::get('/create', App\Livewire\BankAccounts\Create::class)
            ->middleware('permission:bank-accounts.create')
            ->name('create');

        Route::get('/{bankAccount}', App\Livewire\BankAccounts\Show::class)
            ->middleware('permission:bank-accounts.view')
            ->name('show');

        Route::get('/{bankAccount}/edit', App\Livewire\BankAccounts\Edit::class)
            ->middleware('permission:bank-accounts.edit')
            ->name('edit');
    });

    // Expenses Routes
    Route::prefix('expenses')->name('admin.expenses.')->group(function () {
        Route::get('/', App\Livewire\Expenses\Index::class)
            ->middleware('permission:expenses.view')
            ->name('index');

        Route::get('/create', App\Livewire\Expenses\Create::class)
            ->middleware('permission:expenses.create')
            ->name('create');

        Route::get('/{expense}', App\Livewire\Expenses\Show::class)
            ->middleware('permission:expenses.view')
            ->name('show');

        Route::get('/{expense}/edit', App\Livewire\Expenses\Edit::class)
            ->middleware('permission:expenses.edit')
            ->name('edit');
    });

    // Recent Activity Routes
    Route::get('/activities', App\Livewire\Activities\Index::class)
        ->middleware('permission:activities.view')
        ->name('admin.activities.index');

    // Reports Routes
    Route::prefix('reports')->name('admin.reports.')->group(function () {
        // Reports Dashboard/Index
        Route::get('/', [ReportsController::class, 'index'])
            ->middleware('permission:reports.view')
            ->name('index');
            
        // Inventory Reports
        Route::get('/inventory', [ReportsController::class, 'inventory'])
            ->middleware('permission:reports.view')
            ->name('inventory');
            
        // Sales Reports
        Route::get('/sales', [ReportsController::class, 'sales'])
            ->middleware('permission:reports.view')
            ->name('sales');
            
        // Purchase Reports
        Route::get('/purchases', [ReportsController::class, 'purchases'])
            ->middleware('permission:reports.view')
            ->name('purchases');
            
        // Financial Reports
        Route::get('/financial', [ReportsController::class, 'financial'])
            ->middleware('permission:reports.view')
            ->name('financial');
            
        // Activity Logs
        Route::get('/activity', [ReportsController::class, 'activity'])
            ->middleware('permission:reports.view')
            ->name('activity');
    });

    // Settings Routes
    Route::prefix('settings')->name('admin.settings.')->group(function () {
        // Settings Dashboard/Index
        Route::get('/', function() {
            return view('settings.index');
        })->middleware('permission:settings.manage')->name('index');
        
        // Expense Types Routes
        Route::get('/expense-types', App\Livewire\Admin\Settings\ExpenseTypes::class)
            ->middleware('permission:settings.manage')
            ->name('expense-types');
            
        Route::get('/expense-types/create', App\Livewire\Admin\Settings\ExpenseTypes\Create::class)
            ->middleware('permission:settings.manage')
            ->name('expense-types.create');
            
        Route::get('/expense-types/{expenseType}', App\Livewire\Admin\Settings\ExpenseTypes\Show::class)
            ->middleware('permission:settings.manage')
            ->name('expense-types.show');
            
        Route::get('/expense-types/{expenseType}/edit', App\Livewire\Admin\Settings\ExpenseTypes\Edit::class)
            ->middleware('permission:settings.manage')
            ->name('expense-types.edit');
        
        // Departments & Positions Routes
        Route::get('/departments-positions', App\Livewire\Admin\Settings\DepartmentsPositions::class)
            ->middleware('permission:settings.manage')
            ->name('departments-positions');
    });





    // Transfers Management
});

// Stock Card Routes
Route::get('/stock-card', [App\Http\Controllers\StockCardController::class, 'index'])->name('stock-card.index');
Route::get('/stock-card/print', [App\Http\Controllers\StockCardController::class, 'print'])->name('stock-card.print');

// Test route for phone input validation (remove in production)
Route::get('/test-phone', function () {
    return view('test-phone');
})->name('test-phone');

// Test routes for flash messages (remove in production)
Route::get('/test-flash', [App\Http\Controllers\TestFlashController::class, 'show'])->name('test.flash.show');
Route::post('/test-flash', [App\Http\Controllers\TestFlashController::class, 'test'])->name('test.flash');
