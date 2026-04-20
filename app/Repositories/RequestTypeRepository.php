<?php

namespace App\Repositories;

use App\Models\RequestType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class RequestTypeRepository extends BaseRepository
{
    public function __construct(RequestType $model)
    {
        parent::__construct($model);
    }

    /**
     * Get active request types.
     */
    public function getActive(): Collection
    {
        return $this->newQuery()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    /**
     * Get request types with usage statistics.
     */
    public function getWithUsageStats(): Collection
    {
        return $this->newQuery()
            ->withCount('requests')
            ->orderBy('name')
            ->get();
    }

    /**
     * Toggle request type status.
     */
    public function toggleStatus(RequestType $requestType): bool
    {
        return $requestType->update(['is_active' => !$requestType->is_active]);
    }

    /**
     * Get request type by name.
     */
    public function findByName(string $name): ?RequestType
    {
        return $this->newQuery()
            ->where('name', $name)
            ->first();
    }

    /**
     * Check if request type exists.
     */
    public function existsByName(string $name, ?int $excludeId = null): bool
    {
        $query = $this->newQuery()->where('name', $name);
        
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        
        return $query->exists();
    }

    /**
     * Get request types for dropdown.
     */
    public function getForDropdown(): Collection
    {
        return $this->newQuery()
            ->select('id', 'name', 'description')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    /**
     * Create request type with validation.
     */
    public function createWithValidation(array $data): RequestType
    {
        // Check for duplicate name
        if ($this->existsByName($data['name'])) {
            throw new \InvalidArgumentException('Request type with this name already exists');
        }

        return $this->create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'is_active' => $data['is_active'] ?? true,
        ]);
    }

    /**
     * Update request type with validation.
     */
    public function updateWithValidation(RequestType $requestType, array $data): RequestType
    {
        // Check for duplicate name (excluding current)
        if ($this->existsByName($data['name'], $requestType->id)) {
            throw new \InvalidArgumentException('Request type with this name already exists');
        }

        return $this->update($requestType, [
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'is_active' => $data['is_active'] ?? true,
        ]);
    }

    /**
     * Get request types with request counts for admin dashboard.
     */
    public function getWithRequestCounts(): Collection
    {
        return $this->newQuery()
            ->leftJoin('requests', 'request_types.id', '=', 'requests.request_type_id')
            ->selectRaw('
                request_types.id,
                request_types.name,
                request_types.description,
                request_types.is_active,
                COUNT(requests.id) as request_count,
                SUM(CASE WHEN requests.status_id = ? THEN 1 ELSE 0 END) as approved_count,
                SUM(CASE WHEN requests.status_id = ? THEN 1 ELSE 0 END) as pending_count
            ', [\App\Enums\RequestStatus::COMPLETED->value, \App\Enums\RequestStatus::SUBMITTED->value])
            ->groupBy('request_types.id', 'request_types.name', 'request_types.description', 'request_types.is_active')
            ->orderBy('request_count', 'desc')
            ->get();
    }

    /**
     * Get popular request types.
     */
    public function getPopular(int $limit = 10): Collection
    {
        return $this->newQuery()
            ->withCount('requests')
            ->where('is_active', true)
            ->orderBy('requests_count', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get request types by usage trend.
     */
    public function getByUsageTrend(int $months = 6): Collection
    {
        return $this->newQuery()
            ->join('requests', 'request_types.id', '=', 'requests.request_type_id')
            ->selectRaw('
                request_types.name,
                COUNT(requests.id) as total_requests,
                COUNT(CASE WHEN requests.created_at >= ? THEN 1 ELSE 0 END) as recent_requests
            ', [now()->subMonths($months)])
            ->where('requests.created_at', '>=', now()->subMonths($months))
            ->groupBy('request_types.id', 'request_types.name')
            ->orderBy('total_requests', 'desc')
            ->get();
    }

    /**
     * Archive unused request types.
     */
    public function archiveUnused(int $days = 90): int
    {
        $unusedTypes = $this->newQuery()
            ->where('is_active', true)
            ->whereDoesntHave('requests', function ($query) use ($days) {
                $query->where('created_at', '>=', now()->subDays($days));
            })
            ->get();

        $count = 0;
        foreach ($unusedTypes as $type) {
            $this->update($type, ['is_active' => false]);
            $count++;
        }

        return $count;
    }

    /**
     * Get request type statistics for reporting.
     */
    public function getStatistics(): array
    {
        $total = $this->newQuery()->count();
        $active = $this->newQuery()->where('is_active', true)->count();
        $inactive = $total - $active;
        $withRequests = $this->newQuery()->whereHas('requests')->count();

        return [
            'total' => $total,
            'active' => $active,
            'inactive' => $inactive,
            'with_requests' => $withRequests,
            'without_requests' => $total - $withRequests,
            'usage_rate' => $total > 0 ? round(($withRequests / $total) * 100, 1) : 0,
        ];
    }

    /**
     * Bulk update request types.
     */
    public function bulkUpdate(array $typeIds, array $data): int
    {
        return $this->newQuery()
            ->whereIn('id', $typeIds)
            ->update($data);
    }

    /**
     * Delete request type (if no associated requests).
     */
    public function deleteIfSafe(RequestType $requestType): bool
    {
        if ($requestType->requests()->exists()) {
            throw new \InvalidArgumentException('Cannot delete request type with associated requests');
        }

        return $this->delete($requestType);
    }

    /**
     * Get request types for export.
     */
    public function getForExport(): Collection
    {
        return $this->newQuery()
            ->withCount('requests')
            ->orderBy('name')
            ->get();
    }
}
