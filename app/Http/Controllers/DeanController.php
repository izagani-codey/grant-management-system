<?php

namespace App\Http\Controllers;

use App\Enums\RequestStatus;
use App\Models\Request as GrantRequest;
use App\Services\WorkflowTransitionService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;

class DeanController extends BaseController
{
    public function dashboard()
    {
        $pendingDeanQuery = \App\Models\Request::query()
            ->pendingDeanReview()
            ->where(function ($query) {
                $query->where('snapshot_requires_dean_signature', true)
                    ->orWhere(function ($fallbackQuery) {
                        $fallbackQuery
                            ->whereNull('snapshot_requires_dean_signature')
                            ->whereHas('requestType.workflowPolicy', fn ($policyQuery) => $policyQuery->where('requires_dean_signature', true));
                    });
            });

        // Calculate Dean-specific stats
        $stats = [
            'pending_dean' => (clone $pendingDeanQuery)->count(),
            'approved_this_month' => \App\Models\Request::where('status_id', \App\Enums\RequestStatus::DEAN_APPROVED->value)
                ->whereMonth('updated_at', now()->month)
                ->whereYear('updated_at', now()->year)->count(),
            'declined_this_month' => \App\Models\Request::where('status_id', \App\Enums\RequestStatus::REJECTED->value)
                ->whereMonth('updated_at', now()->month)
                ->whereYear('updated_at', now()->year)->count(),
            'total_approved' => \App\Models\Request::where('status_id', \App\Enums\RequestStatus::DEAN_APPROVED->value)->count(),
            'approved' => \App\Models\Request::where('status_id', \App\Enums\RequestStatus::DEAN_APPROVED->value)
                ->whereMonth('updated_at', now()->month)
                ->whereYear('updated_at', now()->year)->count(),
            'rejected' => \App\Models\Request::where('status_id', \App\Enums\RequestStatus::REJECTED->value)
                ->whereMonth('updated_at', now()->month)
                ->whereYear('updated_at', now()->year)->count(),
            'total' => \App\Models\Request::count(),
        ];
        
        $pendingRequests = (clone $pendingDeanQuery)
            ->with(['user', 'requestType'])
            ->latest()
            ->get();
        
        return view('dean.dashboard', compact('stats', 'pendingRequests'));
    }

    public function requests()
    {
        return redirect()->route('dashboard');
    }

    public function show($id)
    {
        $grantRequest = GrantRequest::findOrFail($id);
        $this->authorize('view', $grantRequest);

        // Keep legacy dean route, but use the single shared request detail/action screen.
        return redirect()->route('requests.show', $grantRequest->id);
    }

    public function approve(Request $httpRequest, $id)
    {
        $grantRequest = GrantRequest::findOrFail($id);
        $this->authorize('changeStatus', $grantRequest);

        $signatureData = $httpRequest->input('dean_signature_data') ?: Auth::user()?->signature_data;

        if (empty($signatureData)) {
            return redirect()->back()->with('error', 'Dean signature is required. Please save your profile signature or sign before approving.');
        }

        try {
            WorkflowTransitionService::executeTransition(
                $grantRequest,
                RequestStatus::DEAN_APPROVED,
                [
                    'notes' => $httpRequest->input('notes'),
                    'dean_signature_data' => $signatureData,
                ]
            );
        } catch (AuthorizationException $exception) {
            return redirect()->back()->with('error', $exception->getMessage());
        } catch (\Throwable $exception) {
            report($exception);
            return redirect()->back()->with('error', 'Unable to approve request. Please try again.');
        }

        return redirect()->route('dashboard')
            ->with('success', 'Request approved successfully!');
    }

    public function reject(Request $httpRequest, $id)
    {
        $grantRequest = GrantRequest::findOrFail($id);
        $this->authorize('changeStatus', $grantRequest);

        $signatureData = $httpRequest->input('dean_signature_data') ?: Auth::user()?->signature_data;

        if (empty($signatureData)) {
            return redirect()->back()->with('error', 'Dean signature is required. Please save your profile signature or sign before rejecting.');
        }

        try {
            WorkflowTransitionService::executeTransition(
                $grantRequest,
                RequestStatus::REJECTED,
                [
                    'rejection_reason' => $httpRequest->input('reason'),
                    'dean_signature_data' => $signatureData,
                ]
            );
        } catch (AuthorizationException $exception) {
            return redirect()->back()->with('error', $exception->getMessage());
        } catch (\Throwable $exception) {
            report($exception);
            return redirect()->back()->with('error', 'Unable to reject request. Please try again.');
        }

        return redirect()->route('dashboard')
            ->with('success', 'Request rejected successfully!');
    }
public function returnRequest(Request $httpRequest, $id): RedirectResponse
{
    $grantRequest = GrantRequest::findOrFail($id);
    $this->authorize('changeStatus', $grantRequest);

    try {
        WorkflowTransitionService::executeTransition(
            $grantRequest,
            RequestStatus::RETURNED,
            ['notes' => $httpRequest->input('reason')]
        );
    } catch (AuthorizationException $e) {
        return $this->errorResponse($e->getMessage());
    } catch (\Throwable $e) {
        report($e);
        return $this->errorResponse('Unable to return request. Please try again.');
    }

    return $this->successResponse('Request returned successfully!', 'dashboard');
}
    
}
