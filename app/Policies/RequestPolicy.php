<?php

namespace App\Policies;

use App\Enums\RequestStatus;
use App\Models\Request;
use App\Models\User;
use App\Services\WorkflowTransitionService;
use Illuminate\Auth\Access\Response;

class RequestPolicy
{
    /**
     * Can the user view this request?
     */
    public function view(User $user, Request $request): bool
    {
        // Admission users can only view their own requests
        if ($user->role === 'admission') {
            return $user->id === $request->user_id;
        }

        // Staff and Dean users can view any request
        return in_array($user->role, ['staff1', 'staff2', 'dean', 'admin']);
    }

    /**
     * Can the user view any requests?
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['admission', 'staff1', 'staff2', 'dean']);
    }

    /**
     * Can the user create a new request?
     */
    public function create(User $user): bool
    {
        return $user->role === 'admission';
    }

    /**
     * Can the user update/edit this request?
     */
    public function update(User $user, Request $request): bool
    {
        // Only admission can edit, and only their own requests in specific statuses
        if ($user->role === 'admission') {
            return $user->id === $request->user_id && 
                   RequestStatus::from($request->status_id)->canBeEditedByAdmission();
        }

        return false;
    }

    /**
     * Can the user delete this request?
     */
    public function delete(User $user, Request $request): bool
    {
        // Only admission can delete their own pending requests
        return $user->role === 'admission' && 
               $user->id === $request->user_id && 
               $request->status_id === RequestStatus::SUBMITTED->value;
    }

    /**
     * Can the user change the status of this request?
     */
    public function changeStatus(User $user, Request $request): Response|bool
    {
        if (!in_array($user->role, ['staff1', 'staff2', 'dean'])) {
            return Response::deny('Only staff members and dean can update request status.');
        }

        // Check if user can action the current request status
        $transitions = WorkflowTransitionService::getAllowedTransitions();
        $roleTransitions = $transitions[$user->role] ?? [];

        // Check if user has any allowed transitions from current status
        if (!isset($roleTransitions[$request->status_id]) || empty($roleTransitions[$request->status_id])) {
            return Response::deny('You cannot action this request at its current stage.');
        }

        if ($user->role === 'dean' && !$request->requiresDeanSignature()) {
            return Response::deny('Dean action is not available for this request type.');
        }

        return true;
    }

    /**
     * Can the user add comments to this request?
     */
    public function addComment(User $user, Request $request): bool
    {
        // Admission cannot comment
        if ($user->role === 'admission') {
            return false;
        }

        $currentStatus = RequestStatus::from($request->status_id);

        // Staff1 can comment on requests they can action
        if ($user->role === 'staff1') {
            return $currentStatus->canBeActionedByStaff1();
        }

        // Staff2 can comment on requests they can action
        if ($user->role === 'staff2') {
            if ($request->isFinal()) {
                return false;
            }
            // Staff 2 can only comment on requests they can action or override
            return $currentStatus->canBeActionedByStaff2();
        }

        // Dean can comment on requests they can action
        if ($user->role === 'dean') {
            return $request->canBeActionedByDean();
        }

        return false;
    }

    /**
     * Can the user print/export this request?
     */
    public function print(User $user, Request $request): bool
    {
        return $this->view($user, $request);
    }

    /**
     * Can the user revise/resubmit this request?
     */
    public function revise(User $user, Request $request): bool
    {
        return $user->role === 'admission' &&
               $user->id === $request->user_id &&
               $request->status_id === RequestStatus::RETURNED->value;
    }
}
