<?php

namespace App\Services;

use App\Models\FormTemplate;
use App\Models\RequestType;
use App\Models\User;
use App\Repositories\RequestRepository;
use App\Repositories\StatisticsRepository;
use App\Repositories\UserRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class DashboardService
{
    private RequestRepository $requestRepository;
    private StatisticsRepository $statisticsRepository;
    private UserRepository $userRepository;

    public function __construct(
        ?RequestRepository $requestRepository = null,
        ?StatisticsRepository $statisticsRepository = null,
        ?UserRepository $userRepository = null
    ) {
        $this->requestRepository = $requestRepository ?: app(RequestRepository::class);
        $this->statisticsRepository = $statisticsRepository ?: app(StatisticsRepository::class);
        $this->userRepository = $userRepository ?: app(UserRepository::class);
    }

    /**
     * Get complete dashboard data for user.
     */
    public function getDashboardData(User $user, array $filters = []): array
    {
        // Get general templates (not tied to specific request types)
        $generalTemplates = FormTemplate::with('uploader')
            ->where('is_active', true)
            ->whereDoesntHave('requestTypes')
            ->latest('created_at')
            ->get();

        // Get request-type-specific templates for admission users
        $requestTypeTemplates = collect();
        if ($user->isAdmission()) {
            $requestTypeTemplates = RequestType::where('is_active', true)
                ->with(['templates' => function($query) {
                    $query->where('is_active', true)
                          ->orderBy('sort_order')
                          ->orderBy('created_at');
                }, 'templates.uploader'])
                ->whereHas('templates')
                ->orderBy('name')
                ->get();
        }

        return [
            'displayRequests' => $this->requestRepository->getFilteredRequests($filters, $user),
            'dashboardStats' => $this->statisticsRepository->getDashboardStats($user),
            'requestTypes' => RequestType::where('is_active', true)->orderBy('name')->get(),
            'formTemplates' => $generalTemplates,
            'requestTypeTemplates' => $requestTypeTemplates,
            'urgentRequests' => $this->requestRepository->getUrgentRequests($user),
            'user' => $user,
            'filters' => $filters,
        ];
    }

    /**
     * Get dashboard data for admin users.
     */
    public function getAdminDashboardData(User $user): array
    {
        return [
            'systemStats' => $this->statisticsRepository->getSystemStats(),
            'requestTypeStats' => $this->statisticsRepository->getRequestTypeStats(),
            'userRoleStats' => $this->statisticsRepository->getUserRoleStats(),
            'monthlyTrends' => $this->statisticsRepository->getMonthlyTrends(),
            'performanceMetrics' => $this->statisticsRepository->getPerformanceMetrics(),
            'staffWorkload' => $this->statisticsRepository->getStaffWorkload(),
            'recentUsers' => $this->userRepository->getRecent(10),
            'recentRequests' => $this->requestRepository->getForStaff2($user)->take(10),
        ];
    }

    /**
     * Get request types.
     */
    private function getRequestTypes(): Collection
    {
        return RequestType::where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    /**
     * Get form templates.
     */
    private function getFormTemplates(): Collection
    {
        return FormTemplate::with('uploader')
            ->where('is_active', true)
            ->latest('created_at')
            ->get(['id', 'title', 'file_path', 'uploaded_by', 'created_at']);
    }

   
    /**
     * Get request types (legacy - use getRequestTypes).
     */
    private function getCachedRequestTypes(): Collection
    {
        return $this->getRequestTypes();
    }

    /**
     * Get form templates (legacy - use getFormTemplates).
     */
    private function getCachedFormTemplates(): Collection
    {
        return $this->getFormTemplates();
    }

    /**
     * Get quick stats for dashboard widgets.
     */
    public function getQuickStats(User $user): array
    {
        $stats = $this->statisticsRepository->getDashboardStats($user);
        
        return [
            'totalRequests' => $stats['total'],
            'pendingActions' => $stats['pending_verification'] + $stats['with_staff_2'],
            'urgentRequests' => $this->requestRepository->getUrgentRequests($user, 5)->count(),
            'highPriority' => $stats['high_priority'],
            'approvedToday' => $this->getApprovedToday($user),
        ];
    }

    /**
     * Get requests approved today for user.
     */
    private function getApprovedToday(User $user): int
    {
        $query = \App\Models\Request::trulyApproved()
            ->whereDate('updated_at', today());

        if ($user->role === 'admission') {
            $query->where('user_id', $user->id);
        }

        return $query->count();
    }

    /**
     * Get dashboard filters for role.
     */
    public function getRoleFilters(string $role): array
    {
        $baseFilters = [
            'search' => '',
            'status' => '',
            'type' => '',
            'priority' => '',
            'date_from' => '',
            'date_to' => '',
            'urgent' => false,
        ];

        $roleSpecific = match ($role) {
            'admission' => [
                'search_placeholder' => 'Reference, description...',
                'show_urgent' => false,
            ],
            'staff1' => [
                'search_placeholder' => 'Reference, applicant, email...',
                'show_urgent' => true,
            ],
            'staff2' => [
                'search_placeholder' => 'Reference, applicant, email...',
                'show_urgent' => true,
            ],
            default => []
        };

        return array_merge($baseFilters, $roleSpecific);
    }

    /**
     * Get activity timeline for dashboard.
     */
    public function getActivityTimeline(User $user, int $limit = 20): Collection
    {
        $query = \App\Models\AuditLog::with(['request', 'actor'])
            ->orderBy('created_at', 'desc')
            ->limit($limit);

        // Filter by role
        if ($user->role === 'admission') {
            $query->whereHas('request', fn($q) => $q->where('user_id', $user->id));
        }

        return $query->get();
    }

    /**
     * Get deadline alerts for dashboard.
     */
    public function getDeadlineAlerts(User $user): array
    {
        $urgentRequests = $this->requestRepository->getUrgentRequests($user, 5);
        
        $alerts = [];
        
        foreach ($urgentRequests as $request) {
            $daysUntilDeadline = $request->deadline->diffInDays(now());
            
            $alerts[] = [
                'request' => $request,
                'days_left' => $daysUntilDeadline,
                'urgency_level' => $this->getUrgencyLevel($daysUntilDeadline),
                'message' => $this->getDeadlineMessage($request, $daysUntilDeadline),
            ];
        }

        return $alerts;
    }

    /**
     * Get urgency level for deadline.
     */
    private function getUrgencyLevel(int $daysUntilDeadline): string
    {
        if ($daysUntilDeadline < 0) return 'overdue';
        if ($daysUntilDeadline === 0) return 'due_today';
        if ($daysUntilDeadline === 1) return 'due_tomorrow';
        return 'approaching';
    }

    /**
     * Get deadline message.
     */
    private function getDeadlineMessage($request, int $daysUntilDeadline): string
    {
        if ($daysUntilDeadline < 0) {
            $days = abs($daysUntilDeadline);
            return "Request {$request->ref_number} is {$days} day" . ($days === 1 ? '' : 's') . " overdue";
        }
        
        if ($daysUntilDeadline === 0) {
            return "Request {$request->ref_number} is due today";
        }
        
        if ($daysUntilDeadline === 1) {
            return "Request {$request->ref_number} is due tomorrow";
        }
        
        return "Request {$request->ref_number} is due in {$daysUntilDeadline} days";
    }

    /**
     * Get performance comparison data.
     */
    public function getPerformanceComparison(User $user): array
    {
        $thisMonth = now()->format('Y-m');
        $lastMonth = now()->subMonth()->format('Y-m');
        
        $thisMonthStats = $this->getMonthlyStats($thisMonth, $user);
        $lastMonthStats = $this->getMonthlyStats($lastMonth, $user);
        
        return [
            'this_month' => $thisMonthStats,
            'last_month' => $lastMonthStats,
            'change' => [
                'total' => $thisMonthStats['total'] - $lastMonthStats['total'],
                'approved' => $thisMonthStats['approved'] - $lastMonthStats['approved'],
                'declined' => $thisMonthStats['declined'] - $lastMonthStats['declined'],
                'approval_rate' => $thisMonthStats['approval_rate'] - $lastMonthStats['approval_rate'],
            ],
        ];
    }

    /**
     * Get monthly statistics for user.
     */
    private function getMonthlyStats(string $month, User $user): array
    {
        $query = \App\Models\Request::whereMonth('created_at', substr($month, 5, 2))
            ->whereYear('created_at', substr($month, 0, 4));

        if ($user->role === 'admission') {
            $query->where('user_id', $user->id);
        }

        $total = $query->count();
        $approved = (clone $query)->trulyApproved()->count();
        $declined = $query->where('status_id', \App\Enums\RequestStatus::REJECTED->value)->count();

        return [
            'total' => $total,
            'approved' => $approved,
            'declined' => $declined,
            'approval_rate' => $total > 0 ? round(($approved / $total) * 100, 1) : 0,
        ];
    }

    /**
     * Clear dashboard cache for user.
     */
    public function clearUserCache(User $user): void
    {
        $this->statisticsRepository->clearCache();
        
        // Clear specific user caches
        $patterns = [
            "dashboard_stats_{$user->id}_{$user->role}",
            "unread_notifications_{$user->id}",
        ];
        
        foreach ($patterns as $key) {
            \Illuminate\Support\Facades\Cache::forget($key);
        }
    }
}
