<?php

namespace App\Http\Requests;

use App\Enums\RequestStatus;
use App\Models\Request as GrantRequest;
use App\Models\RequestType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        $grantRequest = GrantRequest::find($this->route('id'));

        return $grantRequest
            && $this->user()
            && $this->user()->role === 'admission'
            && (int) $grantRequest->user_id === (int) $this->user()->id
            && $grantRequest->isReturned();
    }

    public function rules(): array
    {
        $grantRequest  = GrantRequest::find($this->route('id'));
        $requestTypeId = $this->input('request_type_id', $grantRequest?->request_type_id);
        $requestType   = RequestType::find($requestTypeId);
        $requiresVot   = $requestType?->requires_vot ?? false;

        $rules = [
            'request_type_id' => [
                'required',
                Rule::exists('request_types', 'id')->where(function ($query) use ($grantRequest) {
                    if ($grantRequest) {
                        $query->where('id', $grantRequest->request_type_id)->orWhere('is_active', true);
                    } else {
                        $query->where('is_active', true);
                    }
                }),
            ],
            'description'            => 'required|string|max:500',
            'signature_data'         => 'nullable|string',
            'document'               => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'additional_documents'   => 'nullable|array|max:10',
            'additional_documents.*' => ['file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ];

        if ($requiresVot) {
            $rules['vot_items']               = 'required|array|min:1';
            $rules['vot_items.*.vot_code']    = 'required|string|exists:vot_codes,code';
            $rules['vot_items.*.description'] = 'required|string|max:255';
            $rules['vot_items.*.amount']      = 'required|numeric|min:0';
        } else {
            $rules['vot_items'] = 'nullable|array';
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'request_type_id.required'         => 'Please select a request type.',
            'description.required'             => 'Description is required.',
            'vot_items.required'               => 'At least one VOT item is required.',
            'vot_items.*.vot_code.required'    => 'Each VOT item must have a VOT code.',
            'vot_items.*.description.required' => 'Each VOT item must have a description.',
            'vot_items.*.amount.required'      => 'Each VOT item must have an amount.',
        ];
    }
}
