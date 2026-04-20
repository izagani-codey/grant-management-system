<?php

namespace App\Http\Controllers;

use App\Http\Controllers\BaseController;
use App\Models\User;
use App\Repositories\RequestTypeRepository;
use App\Repositories\StatisticsRepository;
use App\Repositories\UserRepository;
use App\Services\DashboardService;
use App\Services\RequestService;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AdminController extends BaseController
{
    public function __construct(
        private DashboardService $dashboardService,
        private UserRepository $userRepository,
        private RequestTypeRepository $requestTypeRepository,
        private StatisticsRepository $statisticsRepository,
        private RequestService $requestService,
        private NotificationService $notificationService
    ) {}

    /**
     * Admin dashboard with system statistics.
     */
    public function dashboard(Request $request)
    {
        $this->authorize('viewAdminDashboard', \App\Models\Request::class);

        $user = $this->currentUser();
        $adminData = $this->dashboardService->getAdminDashboardData($user);

        return view('admin.dashboard', $adminData);
    }

    /**
     * User management page.
     */
    public function users(Request $request)
    {
        $this->authorize('manageUsers', User::class);

        $filters = $request->only(['search', 'role', 'status']);
        $users = $this->getFilteredUsers($filters);

        return view('admin.users', compact('users', 'filters'));
    }

    /**
     * Update user role.
     */
    public function updateUserRole(Request $request, $id)
    {
        $this->authorize('manageUsers', User::class);

        $data = $request->validate([
            'role' => 'required|in:admission,staff1,staff2',
        ]);

        $user = $this->userRepository->findWithRelations($id);
        $this->userRepository->updateRole($user, $data['role']);

        return $this->successResponse('User role updated successfully!');
    }

    /**
     * Toggle user status.
     */
    public function toggleUserStatus(Request $request, $id)
    {
        $this->authorize('manageUsers', User::class);

        $user = $this->userRepository->findWithRelations($id);
        $this->userRepository->toggleStatus($user);

        return $this->successResponse('User status updated successfully!');
    }

    /**
     * Request type management page.
     */
    public function requestTypes(Request $request)
    {
        $this->authorize('manageRequestTypes', \App\Models\RequestType::class);

        $requestTypes = $this->requestTypeRepository->all();
        $stats = $this->statisticsRepository->getRequestTypeStats();

        return view('admin.request-types', compact('requestTypes', 'stats'));
    }

    /**
     * Create new request type.
     */
    public function createRequestType(Request $request)
    {
        $this->authorize('manageRequestTypes', \App\Models\RequestType::class);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
        ]);

        $this->requestTypeRepository->create($data);

        return $this->successResponse('Request type created successfully!');
    }

    /**
     * Update request type.
     */
    public function updateRequestType(Request $request, $id)
    {
        $this->authorize('manageRequestTypes', \App\Models\RequestType::class);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
        ]);

        $requestType = $this->requestTypeRepository->findWithRelations($id);
        $this->requestTypeRepository->update($requestType, $data);

        return $this->successResponse('Request type updated successfully!');
    }

    /**
     * Toggle request type status.
     */
    public function toggleRequestTypeStatus(Request $request, $id)
    {
        $this->authorize('manageRequestTypes', \App\Models\RequestType::class);

        $requestType = $this->requestTypeRepository->findWithRelations($id);
        $this->requestTypeRepository->toggleStatus($requestType);

        return $this->successResponse('Request type status updated successfully!');
    }

    /**
     * System settings page.
     */
    public function settings(Request $request)
    {
        $this->authorize('manageSystem', \App\Models\Request::class);

        $settings = $this->getSystemSettings();
        
        if ($request->isMethod('post')) {
            $this->updateSystemSettings($request->validated());
            return $this->successResponse('Settings updated successfully!');
        }

        return view('admin.settings', compact('settings'));
    }

    /**
     * System logs page.
     */
    public function logs(Request $request)
    {
        $this->authorize('viewLogs', \App\Models\Request::class);

        $filters = $request->only(['date_from', 'date_to', 'user_id', 'action']);
        $logs = $this->getSystemLogs($filters);

        return view('admin.logs', compact('logs', 'filters'));
    }

    /**
     * Send system notification.
     */
    public function sendNotification(Request $request)
    {
        $this->authorize('sendNotifications', \App\Models\Request::class);

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string|max:1000',
            'type' => 'required|in:info,warning,error,success',
            'target_role' => 'nullable|in:admission,staff1,staff2',
            'target_users' => 'nullable|array',
        ]);

        if (!empty($data['target_role'])) {
            $this->notificationService->sendRoleNotification(
                $data['target_role'], 
                $data['title'], 
                $data['message']
            );
        } elseif (!empty($data['target_users'])) {
            foreach ($data['target_users'] as $userId) {
                $user = $this->userRepository->findWithRelations($userId);
                $this->notificationService->createNotification($user, [
                    'title' => $data['title'],
                    'message' => $data['message'],
                    'url' => route('dashboard'),
                    'type' => $data['type'],
                ]);
            }
        } else {
            $this->notificationService->sendSystemNotification(
                $data['title'], 
                $data['message'], 
                $data['type']
            );
        }

        return $this->successResponse('Notification sent successfully!');
    }

    /**
     * Get filtered users for admin.
     */
    private function getFilteredUsers(array $filters)
    {
        $query = \App\Models\User::query();

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['role'])) {
            $query->where('role', $filters['role']);
        }

        if (!empty($filters['status'])) {
            $query->where('is_active', $filters['status'] === 'active');
        }

        return $query->orderBy('name')->paginate(20);
    }

    /**
     * Get system settings.
     */
    private function getSystemSettings(): array
    {
        return [
            'system_name' => config('app.name', 'UniKL STRG Portal'),
            'max_file_size' => config('filesystems.max_file_size', 10240),
            'allowed_file_types' => config('filesystems.allowed_file_types', ['pdf', 'jpg', 'png']),
            'deadline_reminder_days' => config('app.deadline_reminder_days', 3),
            'auto_logout_minutes' => config('session.lifetime', 120),
            'maintenance_mode' => config('app.maintenance', false),
        ];
    }

    /**
     * Update system settings.
     */
    private function updateSystemSettings(array $data): void
    {
        // This would typically update config files or database
        // For demo purposes, we'll just cache the settings
        \Illuminate\Support\Facades\Cache::put('system_settings', $data, 3600);
    }

    /**
     * Get system logs.
     */
    private function getSystemLogs(array $filters)
    {
        $query = \App\Models\AuditLog::with(['request', 'actor'])
            ->orderBy('created_at', 'desc');

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        if (!empty($filters['user_id'])) {
            $query->where('actor_id', $filters['user_id']);
        }

        if (!empty($filters['action'])) {
            $query->where('to_status', $filters['action']);
        }

        return $query->paginate(50);
    }

    /**
     * Get system statistics for API.
     */
    public function getStats(Request $request)
    {
        $this->authorize('viewAdminDashboard', \App\Models\Request::class);

        $period = $request->get('period', 'month');
        $stats = match ($period) {
            'today' => $this->getTodayStats(),
            'week' => $this->getWeekStats(),
            'month' => $this->statisticsRepository->getSystemStats(),
            'year' => $this->getYearStats(),
            default => $this->statisticsRepository->getSystemStats(),
        };

        return $this->apiResponse($stats);
    }

    /**
     * Get today's statistics.
     */
    private function getTodayStats(): array
    {
        return [
            'requests_today' => \App\Models\Request::whereDate('created_at', today())->count(),
            'approved_today' => \App\Models\Request::where('status_id', \App\Enums\RequestStatus::COMPLETED->value)
                ->whereDate('updated_at', today())->count(),
            'active_users_today' => \App\Models\User::whereDate('last_login_at', today())->count(),
            'notifications_sent_today' => \App\Models\Notification::whereDate('created_at', today())->count(),
        ];
    }

    /**
     * Get this week's statistics.
     */
    private function getWeekStats(): array
    {
        return [
            'requests_this_week' => \App\Models\Request::whereBetween('created_at', [
                now()->startOfWeek(),
                now()->endOfWeek()
            ])->count(),
            'approved_this_week' => \App\Models\Request::where('status_id', \App\Enums\RequestStatus::COMPLETED->value)
                ->whereBetween('updated_at', [now()->startOfWeek(), now()->endOfWeek()])
                ->count(),
            'active_users_this_week' => \App\Models\User::whereBetween('last_login_at', [
                now()->startOfWeek(),
                now()->endOfWeek()
            ])->count(),
        ];
    }

    /**
     * Get this year's statistics.
     */
    private function getYearStats(): array
    {
        return [
            'requests_this_year' => \App\Models\Request::whereYear('created_at', now()->year)->count(),
            'approved_this_year' => \App\Models\Request::where('status_id', \App\Enums\RequestStatus::COMPLETED->value)
                ->whereYear('updated_at', now()->year)->count(),
            'total_users' => \App\Models\User::count(),
            'active_users' => \App\Models\User::where('is_active', true)->count(),
        ];
    }

    /**
     * Clear system cache.
     */
    public function clearCache(Request $request)
    {
        $this->authorize('manageSystem', \App\Models\Request::class);

        $this->statisticsRepository->clearCache();
        
        return $this->successResponse('System cache cleared successfully!');
    }
}
