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
                'id' => 'submitted',
                'status' => RequestStatus::SUBMITTED,
                'label' => 'Submitted',
                'description' => 'Request submitted by applicant',
                'icon' => 'M9 12h6m-6 0 01-2.5 2.5 0 01H4a2 2 0 012.5 0 01v6a2 2 0 012.5 0 01z',
            ],
            [
                'id' => 'verified',
                'status' => RequestStatus::STAFF1_APPROVED,
                'label' => 'Staff 1 Verification',
                'description' => 'Request verified by Staff 1',
                'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
            ],
            [
                'id' => 'recommended',
                'status' => RequestStatus::STAFF2_APPROVED,
                'label' => 'Staff 2 Recommendation',
                'description' => 'Request reviewed and recommended by Staff 2',
                'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
            ],
            [
                'id' => 'completed',
                'status' => RequestStatus::DEAN_APPROVED,
                'label' => 'Completed',
                'description' => 'Request approved and completed',
                'icon' => 'M5 13l4 4L19 7',
            ],
        ];
    }

    private function getCurrentStep(): int
    {
        if ($this->request->isTrulyComplete()) {
            return 3;
        }

        $currentStatus = $this->request->getStatus();
        
        return match($currentStatus) {
            RequestStatus::SUBMITTED => 0,
            RequestStatus::STAFF1_APPROVED => 1,
            RequestStatus::RETURNED => 1, // Back to verification
            RequestStatus::STAFF2_APPROVED => 2,
            RequestStatus::REJECTED => 2, // Rejected at any step
            default => 0,
        };
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
