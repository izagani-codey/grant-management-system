<?php

namespace App\Http\Requests;

use App\Models\RequestType;
use Illuminate\Foundation\Http\FormRequest;

class StoreRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->role === 'admission';
    }

    public function rules(): array
    {
        $requestType       = RequestType::find($this->input('request_type_id'));
        $requiresVot       = $requestType?->requires_vot ?? false;
        $requiresSignature = $requestType?->requires_signature ?? false;

        $mimeTypes = 'mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx';
        $mimeCheck = 'mimetypes:application/pdf,image/jpeg,image/png,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

        $rules = [
            'request_type_id' => 'required|exists:request_types,id',
            'description'     => 'required|string|max:2000',
            'field_values'    => 'nullable|array',
            'documents'       => 'nullable|array',
            'documents.*'     => ['file', $mimeTypes, $mimeCheck, 'max:5120'],
        ];

        if ($requiresSignature) {
            $rules['signature_data'] = ['required', 'string', 'starts_with:data:image/png;base64,', 'max:819200'];
        } else {
            $rules['signature_data'] = ['nullable', 'string', 'starts_with:data:image/png;base64,', 'max:819200'];
        }

        if ($requiresVot) {
            $rules['vot_items']               = 'required|array|min:1';
            $rules['vot_items.*.vot_code']    = 'required|string|exists:vot_codes,code';
            $rules['vot_items.*.description'] = 'required|string|max:255';
            $rules['vot_items.*.amount']      = 'required|numeric|min:0';
        } else {
            $rules['vot_items'] = 'nullable|array';
        }

        if ($requestType && $requestType->field_schema) {
            foreach ($requestType->field_schema as $field) {
                $fieldName  = "field_values.{$field['name']}";
                $isRequired = $field['required'] ?? false;

                $rules[$fieldName] = match ($field['type'] ?? 'text') {
                    'number'   => $isRequired ? 'required|numeric' : 'nullable|numeric',
                    'date'     => $isRequired ? 'required|date' : 'nullable|date',
                    'checkbox' => 'nullable|boolean',
                    default    => $isRequired ? 'required|string' : 'nullable|string',
                };
            }
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'request_type_id.required'         => 'Please select a request type.',
            'request_type_id.exists'           => 'Selected request type is invalid.',
            'description.required'             => 'Description is required.',
            'vot_items.required'               => 'At least one VOT item is required.',
            'vot_items.*.vot_code.required'    => 'Each VOT item must have a VOT code.',
            'vot_items.*.vot_code.exists'      => 'Invalid VOT code selected.',
            'vot_items.*.description.required' => 'Each VOT item must have a description.',
            'vot_items.*.amount.required'      => 'Each VOT item must have an amount.',
            'vot_items.*.amount.min'           => 'VOT amount must be zero or greater.',
            'signature_data.required'          => 'Please sign the form before submitting.',
            'documents.*.max'                  => 'Each file must not exceed 5MB.',
        ];
    }
}
