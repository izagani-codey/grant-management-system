<?php

namespace App\Services;

use App\Enums\RequestStatus;
use App\Models\Document;
use App\Models\Request as GrantRequest;
use App\Models\RequestType;
use App\Models\User;
use App\Models\VotCode;
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
        $generalTemplates = Document::with('uploader')
            ->where('document_type', 'template')
            ->where('is_active', true)
            ->whereNull('request_type_id')
            ->latest('created_at')
            ->get();

        // Get request-type-specific templates for admission users
        $requestTypeTemplates = collect();
        if ($user->isAdmission()) {
            $requestTypeTemplates = RequestType::where('is_active', true)
                ->with(['templates' => function($query) {
                    $query->where('is_active', true)
                          ->orderBy('created_at');
                }, 'templates.uploader'])
                ->whereHas('templates')
                ->orderBy('name')
                ->get();
        }

        $data = [
            'displayRequests' => $this->requestRepository->getFilteredRequests($filters, $user),
            'dashboardStats' => $this->statisticsRepository->getDashboardStats($user),
            'requestTypes' => RequestType::where('is_active', true)->orderBy('name')->get(),
            'formTemplates' => $generalTemplates,
            'requestTypeTemplates' => $requestTypeTemplates,
            'user' => $user,
            'filters' => $filters,
        ];

        if ($user->isStaff1()) {
            $data['submittedQueue'] = GrantRequest::with(['requestType', 'user'])
                ->where('status_id', RequestStatus::SUBMITTED->value)
                ->orderBy('ref_number')
                ->get();
            $data['approvedQueue'] = GrantRequest::with(['requestType', 'user'])
                ->where('status_id', RequestStatus::STAFF2_APPROVED->value)
                ->orderBy('ref_number')
                ->get();
        }

        if ($user->isStaff2()) {
            $data['myQueue'] = GrantRequest::with(['requestType', 'user'])
                ->where('status_id', RequestStatus::STAFF1_REVIEWED->value)
                ->orderBy('ref_number')
                ->get();
            $data['overrideQueue'] = GrantRequest::with(['requestType', 'user'])
                ->where('status_id', RequestStatus::SUBMITTED->value)
                ->orderBy('ref_number')
                ->get();
            $data['configRequestTypes'] = RequestType::with([
                'activeTemplates.requestType',
                'checklistItems' => fn($q) => $q->orderBy('sort_order'),
            ])->orderBy('name')->get();
            $data['configVotCodes'] = VotCode::active()->ordered()->get();
        }

        return $data;
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
     * Get quick stats for dashboard widgets.
     */
    public function getQuickStats(User $user): array
    {
        $stats = $this->statisticsRepository->getDashboardStats($user);
        
        return [
            'totalRequests' => $stats['total'],
            'pendingActions' => $stats['submitted'] + $stats['staff1_reviewed'] + $stats['staff2_approved'],
            'approvedToday' => $this->getApprovedToday($user),
        ];
    }

    /**
     * Get requests approved today for user.
     */
    private function getApprovedToday(User $user): int
    {
        $query = \App\Models\Request::where('status_id', \App\Enums\RequestStatus::COMPLETED->value)
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
            'date_from' => '',
            'date_to' => '',
        ];

        $roleSpecific = match ($role) {
            'admission' => [
                'search_placeholder' => 'Reference, description...',
                'show_urgent' => false,
            ],
            'staff1' => [
                'search_placeholder' => 'Reference, applicant, email...',
            ],
            'staff2' => [
                'search_placeholder' => 'Reference, applicant, email...',
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
        $approved = (clone $query)->where('status_id', \App\Enums\RequestStatus::COMPLETED->value)->count();
        $declined = (clone $query)->where('status_id', \App\Enums\RequestStatus::DECLINED->value)->count();

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
