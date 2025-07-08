<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Dashboard\DashboardService;
use App\Services\Dashboard\ChartDataService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class DashboardController extends Controller
{
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
            
            if ($user->hasRole(['SystemAdmin', 'Manager'])) {
                $branchId = $request->get('branch_id') ? (int) $request->get('branch_id') : null;
                $warehouseId = $request->get('warehouse_id') ? (int) $request->get('warehouse_id') : null;
            }
            
            $dashboardData = $this->dashboardService->getDashboardData($user, $branchId, $warehouseId);
            
            // Add available branches and warehouses for filter dropdowns
            if ($user->hasRole(['SystemAdmin', 'Manager'])) {
                $dashboardData['available_branches'] = \App\Models\Branch::select('id', 'name')->get();
                $dashboardData['available_warehouses'] = \App\Models\Warehouse::select('id', 'name')->get();
            }
            
            return view('dashboard', $dashboardData);
        } catch (\Exception $e) {
            \Log::error('Dashboard error: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return view('dashboard', $this->dashboardService->getEmptyDashboardData());
        }
    }

    /**
     * Get chart data for AJAX requests
     */
    public function getChartData(Request $request, string $range = 'month'): JsonResponse
    {
        try {
            $user = auth()->user();
            $chartData = $this->chartDataService->getChartData($user, $range);
            
            return response()->json($chartData);
        } catch (\Exception $e) {
            \Log::error('Chart data error: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'range' => $range,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json($this->chartDataService->getEmptyChartData(), 500);
        }
    }
}
