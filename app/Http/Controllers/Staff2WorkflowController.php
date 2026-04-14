<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\RequestType;
use Illuminate\Http\Request;

class Staff2WorkflowController extends BaseController
{
    public function index()
    {
        $requestTypes = RequestType::query()
            ->withCount('requests')
            ->with('workflowPolicy')
            ->orderBy('name')
            ->get();

        return view('staff2.workflow-settings', compact('requestTypes'));
    }

    public function update(Request $request, RequestType $requestType)
    {
        $validated = $request->validate([
            'requires_dean_signature' => ['required', 'boolean'],
        ]);

        $policy = $requestType->workflowPolicy()->firstOrCreate(
            [],
            ['requires_dean_signature' => true]
        );

        $policy->update([
            'requires_dean_signature' => (bool) $validated['requires_dean_signature'],
        ]);

        AuditLog::create([
            'actor_id' => auth()->id(),
            'actor_role' => auth()->user()->role,
            'action' => 'workflow_policy_updated',
            'request_type_id' => $requestType->id,
            'note' => sprintf(
                'Updated workflow policy for %s: requires_dean_signature=%s',
                $requestType->name,
                $policy->requires_dean_signature ? 'true' : 'false'
            ),
        ]);

        return redirect()
            ->route('staff2.workflow.index')
            ->with('success', 'Workflow policy updated successfully.');
    }
}
