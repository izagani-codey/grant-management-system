<?php

namespace App\Repositories;

use App\Enums\RequestStatus;
use App\Models\Request as GrantRequest;
use App\Models\User;
use Illuminate\Cache\RedisStore;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class StatisticsRepository
{
    /**
     * Get cached dashboard statistics for a user.
     */
    public function getDashboardStats(User $user): array
    {
        $cacheKey = "dashboard_stats_{$user->id}_{$user->role}";
        
        return Cache::remember($cacheKey, 900, function () use ($user) {
            return $this->calculateDashboardStats($user);
        });
    }

    /**
     * Calculate dashboard statistics.
     */
    private function calculateDashboardStats(User $user): array
    {
        $base = GrantRequest::query();
        
        if ($user->role === 'admission') {
            $base->where('user_id', $user->id);
        }

        $counts = (clone $base)
            ->selectRaw('status_id, COUNT(*) as total')
            ->groupBy('status_id')
            ->pluck('total', 'status_id');

        return [
            'total' => (clone $base)->count(),
            'submitted' => (int) ($counts[RequestStatus::SUBMITTED->value] ?? 0),
            'staff1_reviewed' => (int) ($counts[RequestStatus::STAFF1_REVIEWED->value] ?? 0),
            'staff2_approved' => (int) ($counts[RequestStatus::STAFF2_APPROVED->value] ?? 0),
            'completed' => (int) ($counts[RequestStatus::COMPLETED->value] ?? 0),
            'returned' => (int) ($counts[RequestStatus::RETURNED->value] ?? 0),
            'declined' => (int) ($counts[RequestStatus::DECLINED->value] ?? 0),
        ];
    }

    /**
     * Get system-wide statistics for admin users.
     */
    public function getSystemStats(): array
    {
        return Cache::remember('system_stats', 3600, function () {
            return [
                'total_requests' => GrantRequest::count(),
                'total_users' => User::count(),
                'submitted' => GrantRequest::where('status_id', RequestStatus::SUBMITTED->value)->count(),
                'staff1_reviewed' => GrantRequest::where('status_id', RequestStatus::STAFF1_REVIEWED->value)->count(),
                'completed_today' => GrantRequest::where('status_id', RequestStatus::COMPLETED->value)
                    ->whereDate('updated_at', today())->count(),
            ];
        });
    }

    /**
     * Get request type statistics.
     */
    public function getRequestTypeStats(): Collection
    {
        return Cache::remember('request_type_stats', 3600, function () {
            return GrantRequest::join('request_types', 'requests.request_type_id', '=', 'request_types.id')
                ->selectRaw('request_types.name, COUNT(*) as count')
                ->groupBy('request_types.id', 'request_types.name')
                ->orderBy('count', 'desc')
                ->get();
        });
    }

    /**
     * Get user role distribution.
     */
    public function getUserRoleStats(): Collection
    {
        return Cache::remember('user_role_stats', 3600, function () {
            return User::selectRaw('role, COUNT(*) as count')
                ->groupBy('role')
                ->orderBy('count', 'desc')
                ->get();
        });
    }

    /**
     * Get monthly request trends.
     */
    public function getMonthlyTrends(int $months = 12): Collection
    {
        return Cache::remember("monthly_trends_{$months}", 3600, function () use ($months) {
            return GrantRequest::selectRaw('
                    DATE_FORMAT(created_at, "%Y-%m") as month,
                    COUNT(*) as total,
                    SUM(CASE WHEN status_id = ? THEN 1 ELSE 0 END) as approved,
                    SUM(CASE WHEN status_id = ? THEN 1 ELSE 0 END) as declined
                ', [RequestStatus::COMPLETED->value, RequestStatus::DECLINED->value])
                ->where('created_at', '>=', now()->subMonths($months))
                ->groupBy('month')
                ->orderBy('month')
                ->get();
        });
    }

    /**
     * Get performance metrics.
     */
    public function getPerformanceMetrics(): array
    {
        return Cache::remember('performance_metrics', 1800, function () {
            $avgProcessingTime = GrantRequest::where('status_id', RequestStatus::COMPLETED->value)
                ->selectRaw('AVG(TIMESTAMPDIFF(DAY, created_at, updated_at)) as avg_days')
                ->value('avg_days') ?? 0;

            $requestsPerDay = GrantRequest::where('created_at', '>=', now()->subDays(30))
                ->count() / 30;

            return [
                'avg_processing_days' => round($avgProcessingTime, 1),
                'requests_per_day' => round($requestsPerDay, 1),
                'approval_rate' => $this->calculateApprovalRate(),
            ];
        });
    }

    /**
     * Calculate approval rate.
     */
    private function calculateApprovalRate(): float
    {
        $completed = GrantRequest::where('status_id', RequestStatus::COMPLETED->value)->count();
        $declined = GrantRequest::where('status_id', RequestStatus::DECLINED->value)->count();
        $total = $completed + $declined;

        if ($total === 0) {
            return 0;
        }

        $approved = $completed;

        return round(($approved / $total) * 100, 1);
    }

    /**
     * Clear statistics cache.
     */
    public function clearCache(): void
    {
        $fixedKeys = [
            'system_stats',
            'request_type_stats',
            'user_role_stats',
            'performance_metrics',
            'staff_workload',
        ];

        foreach ($fixedKeys as $key) {
            Cache::forget($key);
        }

        $roles = ['admission', 'staff1', 'staff2', 'admin'];
        User::query()->select('id', 'role')->chunkById(200, function ($users) use ($roles) {
            foreach ($users as $user) {
                $role = in_array($user->role, $roles, true) ? $user->role : null;
                if ($role) {
                    Cache::forget("dashboard_stats_{$user->id}_{$role}");
                }
            }
        });

        // Monthly trend caches are keyed by year/month and cannot be enumerated on
        // non-Redis stores; for Redis we can safely clear wildcard patterns.
        if (Cache::getStore() instanceof RedisStore) {
            $redis = Cache::getRedis();
            foreach (['monthly_trends_*', 'dashboard_stats_*'] as $pattern) {
                $keys = $redis->keys($pattern);
                if (!empty($keys)) {
                    $redis->del($keys);
                }
            }
        }
    }

    /**
     * Get staff workload statistics.
     */
    public function getStaffWorkload(): Collection
    {
        return Cache::remember('staff_workload', 1800, function () {
            $staff1 = GrantRequest::query()
                ->selectRaw('verified_by as staff_id, "staff1" as role, COUNT(*) as handled')
                ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, created_at, updated_at)) as avg_hours')
                ->whereNotNull('verified_by')
                ->groupBy('verified_by')
                ->get();

            $staff2 = GrantRequest::query()
                ->selectRaw('recommended_by as staff_id, "staff2" as role, COUNT(*) as handled')
                ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, created_at, updated_at)) as avg_hours')
                ->whereNotNull('recommended_by')
                ->groupBy('recommended_by')
                ->get();

            return $staff1->concat($staff2)->values();
        });
    }
}
