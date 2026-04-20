<?php

namespace App\View\Components;

use App\Enums\RequestStatus;
use App\Models\Request as GrantRequest;
use Illuminate\View\Component;
use Illuminate\View\View;

class RequestTimeline extends Component
{
    public function __construct(
        public GrantRequest $request
    ) {}

    public function render(): View
    {
        $timelineSteps = $this->getTimelineSteps();
        $currentStep = $this->getCurrentStep();

        return view('components.request-timeline', [
            'timelineSteps' => $timelineSteps,
            'currentStep' => $currentStep,
        ]);
    }

    private function getTimelineSteps(): array
    {
        return [
            [
                'id'          => 'submitted',
                'status'      => RequestStatus::SUBMITTED,
                'label'       => 'Submitted',
                'description' => 'Request submitted by applicant',
                'icon'        => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
            ],
            [
                'id'          => 'staff1_reviewed',
                'status'      => RequestStatus::STAFF1_REVIEWED,
                'label'       => 'Staff 1 Checked',
                'description' => 'Validated and checked by Staff 1',
                'icon'        => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
            ],
            [
                'id'          => 'staff2_approved',
                'status'      => RequestStatus::STAFF2_APPROVED,
                'label'       => 'Staff 2 Approved',
                'description' => 'Final approval by Staff 2 with signature',
                'icon'        => 'M5 13l4 4L19 7',
            ],
            [
                'id'          => 'completed',
                'status'      => RequestStatus::COMPLETED,
                'label'       => 'Completed',
                'description' => 'Manually processed and completed by Staff 1',
                'icon'        => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2',
            ],
        ];
    }

    public function getSpecialStatus(): ?string
    {
        $status = $this->request->getStatus();
        if ($status === RequestStatus::RETURNED) return 'returned';
        if ($status === RequestStatus::DECLINED) return 'declined';
        return null;
    }

    private function getCurrentStep(): int
    {
        return match($this->request->getStatus()) {
            RequestStatus::SUBMITTED       => 0,
            RequestStatus::STAFF1_REVIEWED => 1,
            RequestStatus::STAFF2_APPROVED => 2,
            RequestStatus::COMPLETED       => 3,
            RequestStatus::RETURNED,
            RequestStatus::DECLINED        => $this->getReturnedStep(),
            default                        => 0,
        };
    }

    private function getReturnedStep(): int
    {
        $logs = $this->request->auditLogs ?? collect();
        $lastReviewed = $logs->where('new_status', RequestStatus::STAFF1_REVIEWED->value)->last();
        if ($lastReviewed) return 1;
        return 0;
    }

    private function getStepStatus(int $stepIndex): string
    {
        $currentStep = $this->getCurrentStep();
        
        if ($stepIndex < $currentStep) {
            return 'completed';
        } elseif ($stepIndex === $currentStep) {
            return 'current';
        } else {
            return 'pending';
        }
    }
}
