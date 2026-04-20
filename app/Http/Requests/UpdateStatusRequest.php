<?php

namespace App\Http\Requests;

use App\Enums\RequestStatus;
use App\Models\Request as GrantRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class UpdateStatusRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $user = $this->user();
        if (!$user) return;

        // Fall back to saved profile signature so staff2 doesn't have to redraw every time
        if ($user->role === 'staff2' && empty($this->input('staff2_signature_data')) && !empty($user->signature_data)) {
            $this->merge(['staff2_signature_data' => $user->signature_data]);
        }
    }

    public function authorize(): bool
    {
        $grantRequest = GrantRequest::find($this->route('id'));
        return $grantRequest && $this->user() && Gate::allows('changeStatus', $grantRequest);
    }

    public function rules(): array
    {
        $validStatusValues = implode(',', array_map(
            static fn(RequestStatus $s): int => $s->value,
            RequestStatus::cases()
        ));

        return [
            'status_id'             => "required|integer|in:{$validStatusValues}",
            'notes'                 => 'nullable|string',
            'checklist_data'        => 'nullable|array',
            'checklist_data.*'      => 'string',
            'staff2_signature_data' => 'nullable|string',
            'return_reason'         => 'nullable|string|max:1000',
            'decline_reason'        => 'nullable|string|max:1000',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $role      = $this->user()?->role;
            $newStatus = RequestStatus::tryFrom((int) $this->input('status_id'));

            if ($role === 'staff2' && $newStatus === RequestStatus::STAFF2_APPROVED && empty($this->input('staff2_signature_data'))) {
                $validator->errors()->add('staff2_signature_data', 'Staff 2 signature is required to approve.');
            }

            if ($newStatus === RequestStatus::RETURNED && empty($this->input('return_reason'))) {
                $validator->errors()->add('return_reason', 'Please provide a reason for returning this request.');
            }

            if ($newStatus === RequestStatus::DECLINED && empty($this->input('decline_reason'))) {
                $validator->errors()->add('decline_reason', 'Please provide a reason for declining this request.');
            }
        });
    }
}
