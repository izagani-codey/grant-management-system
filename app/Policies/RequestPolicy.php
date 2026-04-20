<?php

namespace App\Policies;

use App\Enums\RequestStatus;
use App\Models\Request;
use App\Models\User;
use App\Services\WorkflowTransitionService;
use Illuminate\Auth\Access\Response;

class RequestPolicy
{
    public function view(User $user, Request $request): bool
    {
        if ($user->role === 'admission') {
            return $user->id === $request->user_id;
        }
        return in_array($user->role, ['staff1', 'staff2', 'admin']);
    }

    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['admission', 'staff1', 'staff2', 'admin']);
    }

    public function create(User $user): bool
    {
        return $user->role === 'admission';
    }

    public function update(User $user, Request $request): bool
    {
        if ($user->role === 'admission') {
            return $user->id === $request->user_id &&
                   RequestStatus::from($request->status_id)->canBeEditedByAdmission();
        }
        return false;
    }

    public function delete(User $user, Request $request): bool
    {
        return $user->role === 'admission' &&
               $user->id === $request->user_id &&
               in_array($request->status_id, [
                   RequestStatus::SUBMITTED->value,
                   RequestStatus::RETURNED->value,
               ]);
    }

    public function changeStatus(User $user, Request $request): Response|bool
    {
        if (!in_array($user->role, ['staff1', 'staff2'])) {
            return Response::deny('Only staff members can update request status.');
        }

        $roleTransitions = WorkflowTransitionService::getAllowedTransitions()[$user->role] ?? [];

        if (empty($roleTransitions[$request->status_id])) {
            return Response::deny('You cannot action this request at its current stage.');
        }

        return true;
    }

    public function addComment(User $user, Request $request): bool
    {
        if ($user->role === 'admission') return false;

        if (in_array($user->role, ['staff1', 'staff2'])) {
            return !$request->isFinal();
        }

        return false;
    }

    public function print(User $user, Request $request): bool
    {
        return $this->view($user, $request);
    }

    public function revise(User $user, Request $request): bool
    {
        return $user->role === 'admission' &&
               $user->id === $request->user_id &&
               $request->isReturned();
    }
}
