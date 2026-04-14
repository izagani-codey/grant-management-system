<?php

namespace App\Services;

use App\Enums\RequestStatus;
use App\Models\Comment;
use App\Models\Notification;
use App\Models\Request as GrantRequest;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class NotificationService
{
    /**
     * Notify staff about new request.
     */
    public function notifyNewRequest(GrantRequest $request): void
    {
        $staff1Users = User::where('role', 'staff1')->get();
        
        foreach ($staff1Users as $user) {
            $this->createNotification($user, [
                'title' => 'New Request for Verification',
                'message' => "Request {$request->ref_number} from {$request->user->name} requires verification.",
                'url' => route('requests.show', $request->id),
                'type' => 'info',
            ]);
        }
    }

    /**
     * Notify admission when request is returned.
     */
    public function notifyReturnedToAdmission(GrantRequest $request, User $staffUser): void
    {
        $this->createNotification($request->user, [
            'title' => 'Request Returned',
            'message' => "Request {$request->ref_number} was returned by {$staffUser->name}: {$request->rejection_reason}",
            'url' => route('requests.show', $request->id),
            'type' => 'warning',
        ]);
    }

    /**
     * Notify staff1 when request is returned by staff2.
     */
    public function notifyReturnedToStaff1(GrantRequest $request, User $staff2User): void
    {
        $staff1Users = User::where('role', 'staff1')->get();
        
        foreach ($staff1Users as $user) {
            $this->createNotification($user, [
                'title' => 'Request Returned from Staff 2',
                'message' => "Request {$request->ref_number} was returned by {$staff2User->name}: {$request->rejection_reason}",
                'url' => route('requests.show', $request->id),
                'type' => 'warning',
            ]);
        }
    }

    /**
     * Notify admission when request is approved.
     */
    public function notifyApproved(GrantRequest $request, User $staff2User): void
    {
        $this->createNotification($request->user, [
            'title' => 'Request Approved!',
            'message' => "Your request {$request->ref_number} has been approved by {$staff2User->name}.",
            'url' => route('requests.show', $request->id),
            'type' => 'success',
        ]);
    }

    /**
     * Notify admission when request is declined.
     */
    public function notifyDeclined(GrantRequest $request, User $staff2User): void
    {
        $this->createNotification($request->user, [
            'title' => 'Request Declined',
            'message' => "Your request {$request->ref_number} was declined by {$staff2User->name}: {$request->rejection_reason}",
            'url' => route('requests.show', $request->id),
            'type' => 'error',
        ]);
    }

    /**
     * Notify about new comment.
     */
    public function notifyNewComment(Comment $comment): void
    {
        $request = $comment->request;
        $excludeUsers = [$comment->user_id];

        // Notify staff involved in request
        $staffUsers = User::whereIn('role', ['staff1', 'staff2'])
            ->whereNotIn('id', $excludeUsers)
            ->get();

        foreach ($staffUsers as $user) {
            $this->createNotification($user, [
                'title' => 'New Comment',
                'message' => "{$comment->user->name} commented on request {$request->ref_number}",
                'url' => route('requests.show', $request->id) . '#comments',
                'type' => 'info',
            ]);
        }

        // Notify admission user if they're involved
        if ($request->user_id !== $comment->user_id) {
            $this->createNotification($request->user, [
                'title' => 'New Comment',
                'message' => "{$comment->user->name} commented on your request {$request->ref_number}",
                'url' => route('requests.show', $request->id) . '#comments',
                'type' => 'info',
            ]);
        }
    }

    /**
     * Notify about deadline approaching.
     */
    public function notifyDeadlineApproaching(GrantRequest $request): void
    {
        $daysUntilDeadline = $request->deadline->diffInDays(now());
        
        if ($daysUntilDeadline <= 3 && $daysUntilDeadline > 0) {
            $message = $daysUntilDeadline === 1 
                ? "Request {$request->ref_number} is due tomorrow!"
                : "Request {$request->ref_number} is due in {$daysUntilDeadline} days.";

            // Notify relevant staff
            $staffUsers = User::whereIn('role', ['staff1', 'staff2'])->get();
            
            foreach ($staffUsers as $user) {
                $this->createNotification($user, [
                    'title' => 'Deadline Approaching',
                    'message' => $message,
                    'url' => route('requests.show', $request->id),
                    'type' => 'warning',
                ]);
            }
        }
    }

    /**
     * Notify about overdue requests.
     */
    public function notifyOverdueRequests(): void
    {
        $overdueRequests = GrantRequest::where('deadline', '<', now())
            ->whereNotIn('status_id', [
                RequestStatus::DEAN_APPROVED->value,
                RequestStatus::REJECTED->value
            ])
            ->with('user')
            ->get();

        foreach ($overdueRequests as $request) {
            $staffUsers = User::whereIn('role', ['staff1', 'staff2'])->get();
            
            foreach ($staffUsers as $user) {
                $this->createNotification($user, [
                    'title' => 'Overdue Request',
                    'message' => "Request {$request->ref_number} is overdue by {$request->deadline->diffInDays(now())} days.",
                    'url' => route('requests.show', $request->id),
                    'type' => 'error',
                ]);
            }
        }
    }

    /**
     * Create notification for user.
     */
    public function createNotification(User $user, array $data): void
    {
        Notification::create([
            'user_id' => $user->id,
            'title' => $data['title'],
            'message' => $data['message'],
            'url' => $data['url'],
            'type' => $data['type'],
            'is_read' => false,
        ]);

        // Clear notification cache for user
        $this->clearUserNotificationCache($user->id);
    }

    /**
     * Mark notifications as read.
     */
    public function markAsRead(int $notificationId, User $user): bool
    {
        $updated = Notification::where('id', $notificationId)
            ->where('user_id', $user->id)
            ->update(['is_read' => true]);

        if ($updated) {
            $this->clearUserNotificationCache($user->id);
        }

        return $updated;
    }

    /**
     * Mark all notifications as read for user.
     */
    public function markAllAsRead(User $user): int
    {
        $updated = Notification::where('user_id', $user->id)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        if ($updated) {
            $this->clearUserNotificationCache($user->id);
        }

        return $updated;
    }

    /**
     * Get unread notifications count for user.
     */
    public function getUnreadCount(User $user): int
    {
        $cacheKey = "unread_notifications_{$user->id}";
        
        return Cache::remember($cacheKey, 300, function () use ($user) {
            return Notification::where('user_id', $user->id)
                ->where('is_read', false)
                ->count();
        });
    }

    /**
     * Get notifications for user.
     */
    public function getUserNotifications(User $user, int $limit = 50)
    {
        return Notification::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Delete old notifications.
     */
    public function deleteOldNotifications(int $days = 30): int
    {
        return Notification::where('created_at', '<', now()->subDays($days))
            ->delete();
    }

    /**
     * Clear user notification cache.
     */
    private function clearUserNotificationCache(int $userId): void
    {
        Cache::forget("unread_notifications_{$userId}");
    }

    /**
     * Send system notification to all users.
     */
    public function sendSystemNotification(string $title, string $message, string $type = 'info'): void
    {
        $users = User::where('is_active', true)->get();
        
        foreach ($users as $user) {
            $this->createNotification($user, [
                'title' => $title,
                'message' => $message,
                'url' => route('dashboard'),
                'type' => $type,
            ]);
        }
    }

    /**
     * Send notification to specific role.
     */
    public function sendRoleNotification(string $role, string $title, string $message, string $url = null): void
    {
        $users = User::where('role', $role)->where('is_active', true)->get();
        
        foreach ($users as $user) {
            $this->createNotification($user, [
                'title' => $title,
                'message' => $message,
                'url' => $url ?? route('dashboard'),
                'type' => 'info',
            ]);
        }
    }

    /**
     * Get notification statistics.
     */
    public function getStatistics(): array
    {
        return [
            'total' => Notification::count(),
            'unread' => Notification::where('is_read', false)->count(),
            'sent_today' => Notification::whereDate('created_at', today())->count(),
            'sent_this_week' => Notification::whereBetween('created_at', [
                now()->startOfWeek(),
                now()->endOfWeek()
            ])->count(),
        ];
    }
}
