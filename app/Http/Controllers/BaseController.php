<?php

namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as LaravelController;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;

abstract class BaseController extends LaravelController
{
    use AuthorizesRequests, ValidatesRequests;

    /**
     * Apply common filters to a query.
     */
    protected function applyFilters(Builder $query, Request $request): void
    {
        $filters = $request->only([
            'search', 'status', 'type', 'date_from', 'date_to'
        ]);

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
    protected function paginateQuery(Builder $query, int $perPage = 15): LengthAwarePaginator
    {
        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Authorize and proceed with action.
     */
    protected function authorizeAndProceed(string $ability, mixed $model): void
    {
        $this->authorize($ability, $model);
    }

    /**
     * Get current authenticated user.
     */
    protected function currentUser()
    {
        return auth()->user();
    }

    /**
     * Check if current user has specific role.
     */
    protected function isRole(string $role): bool
    {
        return $this->currentUser()?->role === $role;
    }

    /**
     * Check if current user is admission.
     */
    protected function isAdmission(): bool
    {
        return $this->isRole('admission');
    }

    /**
     * Check if current user is staff1.
     */
    protected function isStaff1(): bool
    {
        return $this->isRole('staff1');
    }

    /**
     * Check if current user is staff2.
     */
    protected function isStaff2(): bool
    {
        return $this->isRole('staff2');
    }

    /**
     * Return success response.
     */
    protected function successResponse(string $message, string $route = null)
    {
        if ($route) {
            return redirect()->route($route)->with('success', $message);
        }
        return redirect()->back()->with('success', $message);
    }

    /**
     * Return error response.
     */
    protected function errorResponse(string $message, string $route = null)
    {
        if ($route) {
            return redirect()->route($route)->with('error', $message);
        }
        return redirect()->back()->with('error', $message);
    }

    /**
     * Return validation error response.
     */
    protected function validationErrorResponse(array $errors)
    {
        return redirect()->back()
            ->withErrors($errors)
            ->withInput();
    }

    /**
     * Get common view data for dashboards.
     */
    protected function getDashboardData(Request $request): array
    {
        $user = $this->currentUser();
        
        return [
            'user' => $user,
            'filters' => $request->only([
                'search', 'status', 'type', 'date_from', 'date_to'
            ]),
            'hasFilters' => $request->hasAny([
                'search', 'status', 'type', 'date_from', 'date_to'
            ]),
        ];
    }

    /**
     * Apply role-based query modifications.
     */
    protected function applyRoleBasedFiltering(Builder $query, string $role): void
    {
        if ($role === 'admission') {
            $query->where('user_id', $this->currentUser()->id);
        }
    }

    /**
     * Get role-specific search capabilities.
     */
    protected function applyRoleSpecificSearch(Builder $query, string $search): void
    {
        if (!$this->isAdmission()) {
            $query->orWhereHas('user', function ($userQuery) use ($search) {
                $userQuery->where('name', 'like', "%{$search}%")
                       ->orWhere('email', 'like', "%{$search}%");
            });
        }
    }

    /**
     * Standardize API response format.
     */
    protected function apiResponse(mixed $data = null, string $message = '', int $status = 200)
    {
        return response()->json([
            'success' => $status >= 200 && $status < 300,
            'message' => $message,
            'data' => $data,
        ], $status);
    }

    /**
     * Handle not found scenario.
     */
    protected function notFoundResponse(string $resource = 'Resource')
    {
        abort(404, "{$resource} not found.");
    }

    /**
     * Handle unauthorized scenario.
     */
    protected function unauthorizedResponse(string $message = 'Unauthorized')
    {
        abort(403, $message);
    }
}
