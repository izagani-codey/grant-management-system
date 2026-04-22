<?php

namespace App\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

abstract class BaseRepository
{
    protected Model $model;

    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    /**
     * Get a new query builder for the model.
     */
    protected function newQuery(): Builder
    {
        return $this->model->newQuery();
    }

    /**
     * Apply common filters to a query.
     */
    protected function applyFilters(Builder $query, array $filters): void
    {
        foreach ($filters as $key => $value) {
            if (empty($value)) {
                continue;
            }

            match ($key) {
                'search' => $this->applySearchFilter($query, $value),
                'status' => $this->applyStatusFilter($query, $value),
                'type' => $this->applyTypeFilter($query, $value),
                'date_from' => $this->applyDateFromFilter($query, $value),
                'date_to' => $this->applyDateToFilter($query, $value),
                default => null
            };
        }
    }

    /**
     * Apply search filter.
     */
    protected function applySearchFilter(Builder $query, string $search): void
    {
        $query->where(function ($q) use ($search) {
            $q->where('ref_number', 'like', "%{$search}%")
              ->orWhere('payload', 'like', "%{$search}%");
        });
    }

    /**
     * Apply status filter.
     */
    protected function applyStatusFilter(Builder $query, mixed $status): void
    {
        if (\App\Enums\RequestStatus::tryFrom($status)) {
            $query->where('status_id', $status);
        }
    }

    /**
     * Apply type filter.
     */
    protected function applyTypeFilter(Builder $query, mixed $type): void
    {
        $query->where('request_type_id', $type);
    }

    /**
     * Apply date from filter.
     */
    protected function applyDateFromFilter(Builder $query, mixed $dateFrom): void
    {
        $query->whereDate('created_at', '>=', $dateFrom);
    }

    /**
     * Apply date to filter.
     */
    protected function applyDateToFilter(Builder $query, mixed $dateTo): void
    {
        $query->whereDate('created_at', '<=', $dateTo);
    }

    /**
     * Paginate query results.
     */
    protected function paginate(Builder $query, int $perPage = 15): LengthAwarePaginator
    {
        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Find model by ID with relationships.
     */
    public function findWithRelations(int $id, array $relations = []): Model
    {
        $query = $this->newQuery();
        
        if (!empty($relations)) {
            $query->with($relations);
        }

        return $query->findOrFail($id);
    }

    /**
     * Get all models.
     */
    public function all(): Collection
    {
        return $this->newQuery()->get();
    }

    /**
     * Create new model.
     */
    public function create(array $data): Model
    {
        return $this->model->create($data);
    }

    /**
     * Update model.
     */
    public function update(Model $model, array $data): bool
    {
        return $model->update($data);
    }

    /**
     * Delete model.
     */
    public function delete(Model $model): bool
    {
        return $model->delete();
    }
}
