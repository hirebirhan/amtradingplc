<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BankAccountController;
use App\Http\Controllers\Api\V1\BranchController;
use App\Http\Controllers\Api\V1\PurchaseController;
use App\Http\Controllers\Api\V1\SaleController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\CustomerController;
use App\Http\Controllers\Api\V1\EmployeeController;
use App\Http\Controllers\Api\V1\ItemController;
use App\Http\Controllers\Api\V1\ItemImportController;
use App\Http\Controllers\Api\V1\PermissionController;
use App\Http\Controllers\Api\V1\RoleController;
use App\Http\Controllers\Api\V1\StockCardController;
use App\Http\Controllers\Api\V1\StockController;
use App\Http\Controllers\Api\V1\StockHistoryController;
use App\Http\Controllers\Api\V1\StockReportController;
use App\Http\Controllers\Api\V1\StockReservationController;
use App\Http\Controllers\Api\V1\SupplierController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\V1\CreditController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\ExpenseController;
use App\Http\Controllers\Api\V1\PriceHistoryController;
use App\Http\Controllers\Api\V1\ReportController;
use App\Http\Controllers\Api\V1\TransferController;
use App\Http\Controllers\Api\V1\WarehouseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Legacy endpoint — kept for backward compatibility
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::prefix('v1')->group(function () {

    // ── Auth (public) ────────────────────────────────────────────────────────
    Route::post('auth/login', [AuthController::class, 'login'])->middleware('throttle:login');

    // ── Authenticated routes ─────────────────────────────────────────────────
    Route::middleware(['auth:sanctum', 'api.active'])->group(function () {

        Route::post('auth/logout',  [AuthController::class, 'logout']);
        Route::get('me',            [AuthController::class, 'me']);
        Route::patch('me/profile',  [AuthController::class, 'updateProfile']);

        // ── Reference data ───────────────────────────────────────────────────
        Route::apiResource('branches',    BranchController::class);
        Route::apiResource('warehouses',  WarehouseController::class);
        Route::apiResource('categories',  CategoryController::class);

        Route::get('items/search',  [ItemController::class, 'search']);
        Route::apiResource('items', ItemController::class);

        Route::post('items/imports/preview',   [ItemImportController::class, 'preview']);
        Route::post('items/imports',           [ItemImportController::class, 'import']);
        Route::get('items/import-template',    [ItemImportController::class, 'downloadTemplate']);

        Route::apiResource('customers', CustomerController::class);
        Route::apiResource('suppliers', SupplierController::class);

        Route::apiResource('users',    UserController::class);
        Route::get('users/{user}/roles',   [UserController::class, 'roles']);
        Route::post('users/{user}/roles',  [UserController::class, 'syncRoles']);

        Route::apiResource('roles',       RoleController::class);
        Route::get('permissions',         [PermissionController::class, 'index']);

        Route::apiResource('employees',    EmployeeController::class);
        Route::apiResource('bank-accounts', BankAccountController::class);

        // ── Purchases ────────────────────────────────────────────────────────
        Route::apiResource('purchases', PurchaseController::class)->except(['update']);

        // ── Sales ────────────────────────────────────────────────────────────
        Route::apiResource('sales', SaleController::class)->except(['update']);

        // ── Credits ──────────────────────────────────────────────────────────
        Route::get('credits',                                              [CreditController::class, 'index']);
        Route::get('credits/{credit}',                                     [CreditController::class, 'show']);
        Route::get('credits/{credit}/payments',                            [CreditController::class, 'payments']);
        Route::post('credits/{credit}/payments',                           [CreditController::class, 'addPayment']);
        Route::get('credits/{credit}/closing-offer',                       [CreditController::class, 'closingOffer']);
        Route::post('credits/{credit}/closing-offer/calculate',            [CreditController::class, 'calculateClosingOffer']);
        Route::post('credits/{credit}/closing-offer/accept',               [CreditController::class, 'acceptClosingOffer']);

        // ── Transfers ────────────────────────────────────────────────────────
        Route::apiResource('transfers', TransferController::class)->except(['update']);
        Route::post('transfers/{transfer}/approve',         [TransferController::class, 'approve']);
        Route::post('transfers/{transfer}/reject',          [TransferController::class, 'reject']);
        Route::post('transfers/{transfer}/cancel',          [TransferController::class, 'cancel']);
        Route::post('transfers/{transfer}/mark-in-transit', [TransferController::class, 'markInTransit']);
        Route::post('transfers/{transfer}/complete',        [TransferController::class, 'complete']);

        // ── Stock ────────────────────────────────────────────────────────────
        Route::get('stocks',             [StockController::class, 'index']);
        Route::get('stocks/{stock}',     [StockController::class, 'show']);
        Route::get('stock-histories',    [StockHistoryController::class, 'index']);
        Route::get('stock-card',         [StockCardController::class, 'index']);

        Route::get('stock-reservations',                                     [StockReservationController::class, 'index']);
        Route::get('stock-reservations/{stockReservation}',                  [StockReservationController::class, 'show']);
        Route::post('stock-reservations/{stockReservation}/release',         [StockReservationController::class, 'release']);
        Route::patch('stock-reservations/{stockReservation}/extend',         [StockReservationController::class, 'extend']);
        Route::post('stock-reservations/cleanup',                            [StockReservationController::class, 'cleanup']);

        Route::get('stock-reports',         [StockReportController::class, 'index']);
        Route::get('stock-reports/export',  [StockReportController::class, 'export']);

        // ── Expenses & Price History ──────────────────────────────────────────
        Route::apiResource('expenses',       ExpenseController::class);
        Route::get('price-histories',        [PriceHistoryController::class, 'index']);
        Route::get('items/{item}/price-histories', [PriceHistoryController::class, 'forItem']);

        // ── Dashboard & Reports ───────────────────────────────────────────────
        Route::get('dashboard',              [DashboardController::class, 'index']);
        Route::get('dashboard/charts/{range}', [DashboardController::class, 'charts']);
        Route::get('reports/summary',        [ReportController::class, 'summary']);
        Route::get('reports/inventory',      [ReportController::class, 'inventory']);
        Route::get('reports/sales',          [ReportController::class, 'sales']);
        Route::get('reports/purchases',      [ReportController::class, 'purchases']);
        Route::get('reports/financial',      [ReportController::class, 'financial']);
    });
});
