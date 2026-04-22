<?php

namespace App\Http\Controllers;

use App\Http\Controllers\BaseController;
use App\Services\DashboardService;
use Illuminate\Http\Request;

class DashboardController extends BaseController
{
    public function __construct(
        private DashboardService $dashboardService
    ) {}

    public function index(Request $request)
    {
        $user = $this->currentUser();
        $allowedDashboardRoles = ['admission', 'staff1', 'staff2', 'admin'];

        if (!in_array($user->role, $allowedDashboardRoles, true)) {
            abort(403, 'Unauthorized dashboard role.');
        }

        if ($user->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        $filters = $request->only([
            'search', 'status', 'type', 'date_from', 'date_to'
        ]);

        // Get dashboard data using service
        $dashboardData = $this->dashboardService->getDashboardData($user, $filters);
        
        return view('dashboard.' . $user->role, $dashboardData);
    }

    /**
     * Get dashboard statistics via AJAX.
     */
    public function getStats(Request $request)
    {
        $user = $this->currentUser();
        $stats = $this->dashboardService->getQuickStats($user);
        
        return $this->apiResponse($stats);
    }

    /**
     * Get activity timeline for dashboard.
     */
    public function getActivity(Request $request)
    {
        $user = $this->currentUser();
        $limit = $request->get('limit', 20);
        
        $activity = $this->dashboardService->getActivityTimeline($user, $limit);
        
        return $this->apiResponse($activity);
    }

    /**
     * Get performance comparison data.
     */
    public function getPerformanceComparison(Request $request)
    {
        $user = $this->currentUser();
        $comparison = $this->dashboardService->getPerformanceComparison($user);
        
        return $this->apiResponse($comparison);
    }

    /**
     * Clear dashboard cache for user.
     */
    public function clearCache(Request $request)
    {
        $user = $this->currentUser();
        $this->dashboardService->clearUserCache($user);
        
        return $this->successResponse('Dashboard cache cleared');
    }
}
