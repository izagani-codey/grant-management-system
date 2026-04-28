<?php

namespace App\Traits;

use App\Models\Request as GrantRequest;

trait ResolvesPresetZones
{
    /**
     * Resolve preset field value from request data
     */
    private function resolvePresetValue(
        string $tool, 
        GrantRequest $request
    ): string {
        $map = [
            'preset_applicant_name' => 
                $request->submitter_name ?? $request->user->name ?? '',
            'preset_applicant_staff_id' => 
                $request->submitter_staff_id ?? '',
            'preset_applicant_designation' => 
                $request->submitter_designation ?? '',
            'preset_applicant_department' => 
                $request->submitter_department ?? '',
            'preset_applicant_phone' => 
                $request->submitter_phone ?? '',
            'preset_applicant_employee_level' => 
                $request->submitter_employee_level ?? '',
            'preset_submission_date' => 
                $request->submitted_at?->format('d/m/Y') ?? '',
            'preset_reference_number' => 
                $request->ref_number ?? '',
            'preset_final_signatory_name' => 
                $request->final_signatory_name ?? '',
            'preset_final_signatory_designation' => 
                $request->final_signatory_designation ?? '',
            'preset_second_signatory_name' => 
                $request->second_signatory_name ?? '',
            'preset_second_signatory_designation' => 
                $request->second_signatory_designation ?? '',
        ];
        return $map[$tool] ?? '';
    }
}
