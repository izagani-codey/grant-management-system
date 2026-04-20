<?php

namespace App\Repositories;

use App\Enums\RequestStatus;
use App\Models\Request as GrantRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class RequestRepository extends BaseRepository
{
    public function __construct(GrantRequest $model)
    {
        parent::__construct($model);
    }

    /**
     * Get filtered requests for a user.
     */
    public function getFilteredRequests(array $filters, User $user, int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->newQuery()
            ->with(['requestType', 'user', 'verifiedBy', 'recommendedBy'])
            ->latest();

        // Apply role-based filtering
        $this->applyRoleFilter($query, $user);
        
        // Apply search and other filters
        $this->applyFilters($query, $filters);
        
        // Apply role-specific search
        if (!empty($filters['search']) && $user->role !== 'admission') {
            $this->applyRoleSpecificSearch($query, $filters['search']);
        }

        return $this->paginate($query, $perPage);
    }

    /**
     * Apply role-based filtering.
     */
    private function applyRoleFilter(Builder $query, User $user): void
    {
        if ($user->role === 'admission') {
            $query->where('user_id', $user->id);
        }
    }

    /**
     * Apply role-specific search.
     */
    private function applyRoleSpecificSearch(Builder $query, string $search): void
    {
        $query->orWhereHas('user', function ($userQuery) use ($search) {
            $userQuery->where('name', 'like', "%{$search}%")
                   ->orWhere('email', 'like', "%{$search}%");
        });
    }

    /**
     * Get dashboard statistics for a user.
     */
    public function getDashboardStats(User $user): array
    {
        $base = $this->newQuery();
        if ($user->role === 'admission') {
            $base->where('user_id', $user->id);
        }

        $counts = (clone $base)
            ->selectRaw('status_id, COUNT(*) as total')
            ->groupBy('status_id')
            ->pluck('total', 'status_id');

        return [
            'total'          => (clone $base)->count(),
            'submitted'      => (int) ($counts[RequestStatus::SUBMITTED->value] ?? 0),
            'staff1_reviewed' => (int) ($counts[RequestStatus::STAFF1_REVIEWED->value] ?? 0),
            'staff2_approved' => (int) ($counts[RequestStatus::STAFF2_APPROVED->value] ?? 0),
            'completed'      => (int) ($counts[RequestStatus::COMPLETED->value] ?? 0),
            'returned'       => (int) ($counts[RequestStatus::RETURNED->value] ?? 0),
            'declined'       => (int) ($counts[RequestStatus::DECLINED->value] ?? 0),
        ];
    }

    /**
     * Get urgent requests for staff users.
     */
    public function getUrgentRequests(User $user, int $limit = 10): Collection
    {
        return collect();
    }

    /**
     * Get request with all relationships for display.
     */
    public function getForDisplay(int $id): GrantRequest
    {
        return $this->findWithRelations($id, [
            'requestType',
            'user',
            'verifiedBy',
            'recommendedBy',
            'auditLogs.actor',
            'comments.user'
        ]);
    }

    /**
     * Get requests that can be actioned by Staff 1.
     */
    public function getForStaff1(User $user): LengthAwarePaginator
    {
        return $this->newQuery()
            ->with(['requestType', 'user'])
            ->whereIn('status_id', [
                RequestStatus::SUBMITTED->value,
                RequestStatus::RETURNED->value
            ])
            ->latest()
            ->paginate(15);
    }

    /**
     * Get requests that can be actioned by Staff 2.
     */
    public function getForStaff2(User $user): LengthAwarePaginator
    {
        return $this->newQuery()
            ->with(['requestType', 'user'])
            ->whereNotIn('status_id', [
                RequestStatus::COMPLETED->value,
                RequestStatus::DECLINED->value,
            ])
            ->latest()
            ->paginate(15);
    }

    /**
     * Get requests for admission user.
     */
    public function getForAdmission(User $user): LengthAwarePaginator
    {
        return $this->newQuery()
            ->with(['requestType'])
            ->where('user_id', $user->id)
            ->latest()
            ->paginate(15);
    }

    /**
     * Generate unique reference number.
     */
    public function generateReferenceNumber(): string
    {
        do {
            $refNumber = 'REQ-' . date('Y') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
        } while ($this->newQuery()->where('ref_number', $refNumber)->exists());

        return $refNumber;
    }

    /**
     * Get requests by status for dashboard.
     */
    public function getByStatus(RequestStatus $status, User $user): Collection
    {
        $query = $this->newQuery()->where('status_id', $status->value);
        
        if ($user->role === 'admission') {
            $query->where('user_id', $user->id);
        }
        
        return $query->with(['requestType', 'user'])->get();
    }

    /**
     * Get requests approaching deadline.
     */
    public function getApproachingDeadline(int $days = 3, User $user): Collection
    {
        return collect();
    }
}
