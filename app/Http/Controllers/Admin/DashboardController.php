<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Dashboard\DashboardService;
use App\Services\Dashboard\ChartDataService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use App\Facades\UserHelperFacade as UserHelper;
use App\Traits\UsesUserContext;

class DashboardController extends Controller
{
    use UsesUserContext;
    public function __construct(
        private DashboardService $dashboardService,
        private ChartDataService $chartDataService
    ) {
        // Middleware is handled at the route level in web.php
    }

    /**
     * Display the dashboard view with all necessary data
     */
    public function index(Request $request): View
    {
        try {
            $user = auth()->user();
            
            // Get filter parameters (only for SystemAdmin and Manager)
            $branchId = null;
            $warehouseId = null;
            
            // Check user roles
            $isAdminOrManager = UserHelper::isAdminOrManager();
            $isSales = UserHelper::isSales();
            
            if ($isAdminOrManager) {
                $branchId = $request->get('branch_id') ? (int) $request->get('branch_id') : null;
                $warehouseId = $request->get('warehouse_id') ? (int) $request->get('warehouse_id') : null;
            }
            
            // Get dashboard data
            $dashboardData = $this->dashboardService->getDashboardData($user, $branchId, $warehouseId);
            
            // Add role-based view data
            $dashboardData['show_filters'] = $isAdminOrManager;
            $dashboardData['is_sales'] = $isSales;
            $dashboardData['is_admin_or_manager'] = $isAdminOrManager;
            
            // Add available branches and warehouses for filter dropdowns if user has access
            if ($dashboardData['show_filters']) {
                $dashboardData = array_merge($dashboardData, $this->dashboardService->getFilterOptions($user));
            }
            
            // Set page title and description based on user role
            if ($isSales && !$isAdminOrManager) {
                $dashboardData['page_title'] = 'My Dashboard';
                $dashboardData['page_description'] = 'Track your sales and activities';
            } else {
                $dashboardData['page_title'] = 'Dashboard';
                $dashboardData['page_description'] = 'Overview of system activities and metrics';
            }
            
            // Set default values for required variables if not set
            $dashboardData = array_merge([
                'total_sales' => 0,
                'total_revenue' => 0,
                'total_purchases' => 0,
                'total_purchase_amount' => 0,
                'total_inventory_value' => 0,
                'customers_count' => 0,
            ], $dashboardData);
            
            return view('dashboard', $dashboardData);
            
        } catch (\Exception $e) {
            $userId = auth()->id();
            $errorMessage = 'Dashboard data loading failed';
            
            // Log the detailed error
            \Log::error($errorMessage, [
                'error' => $e->getMessage(),
                'user_id' => $userId,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Get empty dashboard data
            $emptyData = $this->dashboardService->getEmptyDashboardData();
            
            // Add user role information for the view
            $emptyData['is_sales'] = UserHelper::isSales();
            $emptyData['is_admin_or_manager'] = UserHelper::isAdminOrManager();
            
            // Add error message
            $emptyData['error'] = 'We encountered an issue loading your dashboard. Our team has been notified. Please try again later.';
            
            // If it's a validation error, show more specific message
            if ($e instanceof \Illuminate\Validation\ValidationException) {
                $emptyData['error'] = 'There was a problem with your request. Please check your input and try again.';
            }
            
            return view('dashboard', $emptyData);
        }
    }

    /**
     * Get chart data for AJAX requests
     */
    public function getChartData(Request $request, string $range = 'month'): JsonResponse
    {
        $allowedRanges = ['today', 'yesterday', 'week', 'month', 'this_month', 'year'];
        if (!in_array($range, $allowedRanges)) {
            return response()->json(['error' => 'Invalid range'], 400);
        }
        
        try {
            $user = auth()->user();
            $branchId = $request->get('branch_id') ? (int) $request->get('branch_id') : null;
            $warehouseId = $request->get('warehouse_id') ? (int) $request->get('warehouse_id') : null;
            
            if (!UserHelper::isAdminOrManager()) {
                $branchId = null;
                $warehouseId = null;
            }
            
            $chartData = $this->chartDataService->getChartData($user, $range, $branchId, $warehouseId);
            return response()->json($chartData);
            
        } catch (\Exception $e) {
            \Log::error('Chart data loading failed', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'range' => $range,
            ]);
            
            return response()->json(['error' => 'Chart data unavailable'], 500);
        }
    }
}
