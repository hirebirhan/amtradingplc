<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BankAccountController;
use App\Http\Controllers\Api\V1\BranchController;
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
    });
});
