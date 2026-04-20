<?php

namespace App\Services;

use App\Models\Comment;
use App\Models\Notification;
use App\Models\Request as GrantRequest;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class NotificationService
{
    public function notifyNewRequest(GrantRequest $request): void
    {
        $this->sendRoleNotification(
            'staff1',
            'New Request for Verification',
            "Request {$request->ref_number} from {$request->user->name} requires verification.",
            route('requests.show', $request->id)
        );
    }

    public function notifyReturnedToUser(GrantRequest $request, User $staffUser): void
    {
        $this->createNotification($request->user, [
            'title'   => 'Request Returned for Revision',
            'message' => "Request {$request->ref_number} was returned by {$staffUser->name}. Please review the feedback and resubmit.",
            'url'     => route('requests.show', $request->id),
            'type'    => 'warning',
        ]);
    }

    public function notifyDeclinedToUser(GrantRequest $request, User $staffUser): void
    {
        $this->createNotification($request->user, [
            'title'   => 'Request Declined',
            'message' => "Request {$request->ref_number} was declined by {$staffUser->name}.",
            'url'     => route('requests.show', $request->id),
            'type'    => 'error',
        ]);
    }

    public function notifyReadyForPrint(GrantRequest $request): void
    {
        $this->sendRoleNotification(
            'staff1',
            'Request Approved — Ready for Processing',
            "Request {$request->ref_number} has been approved. Please process and complete it.",
            route('requests.show', $request->id)
        );
    }

    public function notifyCompleted(GrantRequest $request, User $staff1User): void
    {
        $this->createNotification($request->user, [
            'title'   => 'Request Completed',
            'message' => "Request {$request->ref_number} has been completed and processed.",
            'url'     => route('requests.show', $request->id),
            'type'    => 'success',
        ]);
    }

    public function notifyApproved(GrantRequest $request, User $staff2User): void
    {
        $this->createNotification($request->user, [
            'title'   => 'Request Approved',
            'message' => "Your request {$request->ref_number} has been approved by {$staff2User->name}.",
            'url'     => route('requests.show', $request->id),
            'type'    => 'success',
        ]);
    }

    public function notifyNewComment(Comment $comment): void
    {
        $request      = $comment->request;
        $excludeUsers = [$comment->user_id];

        $staffUsers = User::whereIn('role', ['staff1', 'staff2'])
            ->whereNotIn('id', $excludeUsers)->get();

        foreach ($staffUsers as $user) {
            $this->createNotification($user, [
                'title'   => 'New Comment',
                'message' => "{$comment->user->name} commented on request {$request->ref_number}",
                'url'     => route('requests.show', $request->id) . '#comments',
                'type'    => 'info',
            ]);
        }
    }

    public function createNotification(User $user, array $data): void
    {
        Notification::create([
            'user_id'  => $user->id,
            'title'    => $data['title'],
            'message'  => $data['message'],
            'url'      => $data['url'],
            'type'     => $data['type'],
            'is_read'  => false,
        ]);

        Cache::forget("unread_notifications_{$user->id}");
    }

    public function markAsRead(int $notificationId, User $user): bool
    {
        $updated = Notification::where('id', $notificationId)
            ->where('user_id', $user->id)
            ->update(['is_read' => true]);

        if ($updated) Cache::forget("unread_notifications_{$user->id}");
        return (bool) $updated;
    }

    public function markAllAsRead(User $user): int
    {
        $updated = Notification::where('user_id', $user->id)->where('is_read', false)->update(['is_read' => true]);
        if ($updated) Cache::forget("unread_notifications_{$user->id}");
        return $updated;
    }

    public function getUnreadCount(User $user): int
    {
        return Cache::remember("unread_notifications_{$user->id}", 300, fn () =>
            Notification::where('user_id', $user->id)->where('is_read', false)->count()
        );
    }

    public function getUserNotifications(User $user, int $limit = 50)
    {
        return Notification::where('user_id', $user->id)->orderBy('created_at', 'desc')->limit($limit)->get();
    }

    public function deleteOldNotifications(int $days = 30): int
    {
        return Notification::where('created_at', '<', now()->subDays($days))->delete();
    }

    public function sendSystemNotification(string $title, string $message, string $type = 'info'): void
    {
        foreach (User::where('is_active', true)->get() as $user) {
            $this->createNotification($user, ['title' => $title, 'message' => $message, 'url' => route('dashboard'), 'type' => $type]);
        }
    }

    public function sendRoleNotification(string $role, string $title, string $message, ?string $url = null): void
    {
        foreach (User::where('role', $role)->where('is_active', true)->get() as $user) {
            $this->createNotification($user, ['title' => $title, 'message' => $message, 'url' => $url ?? route('dashboard'), 'type' => 'info']);
        }
    }

    public function getStatistics(): array
    {
        return [
            'total'          => Notification::count(),
            'unread'         => Notification::where('is_read', false)->count(),
            'sent_today'     => Notification::whereDate('created_at', today())->count(),
            'sent_this_week' => Notification::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
        ];
    }
}
