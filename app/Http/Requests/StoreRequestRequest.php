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
        $requestType = RequestType::find($this->input('request_type_id'));
        $requiresVot = $requestType?->requires_vot ?? false;

        $rules = [
            'request_type_id'         => 'required|exists:request_types,id',
            'description'             => 'required|string|max:500',
            'dynamic_fields'          => 'nullable|array',
            'signature_data'          => 'required|string',
            'document'                => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'additional_documents'    => 'nullable|array|max:10',
            'additional_documents.*'  => ['file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ];

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
                $fieldName  = "dynamic_fields.{$field['name']}";
                $isRequired = $field['required'] ?? false;

                $rules[$fieldName] = match ($field['type']) {
                    'number'     => $isRequired ? 'required|numeric' : 'nullable|numeric',
                    'date'       => $isRequired ? 'required|date' : 'nullable|date',
                    'checkbox'   => 'nullable|boolean',
                    default      => $isRequired ? 'required|string' : 'nullable|string',
                };

                if ($field['type'] === 'date_range' && isset($field['fields'])) {
                    foreach ($field['fields'] as $rangeField) {
                        $rules["dynamic_fields.{$rangeField}"] = $isRequired ? 'required|date' : 'nullable|date';
                    }
                }
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
            'description.max'                  => 'Description must not exceed 500 characters.',
            'vot_items.required'               => 'At least one VOT item is required.',
            'vot_items.*.vot_code.required'    => 'Each VOT item must have a VOT code.',
            'vot_items.*.vot_code.exists'      => 'Invalid VOT code selected.',
            'vot_items.*.description.required' => 'Each VOT item must have a description.',
            'vot_items.*.amount.required'      => 'Each VOT item must have an amount.',
            'vot_items.*.amount.min'           => 'VOT amount must be zero or greater.',
            'signature_data.required'          => 'Please sign the form before submitting.',
            'document.max'                     => 'Document file size must not exceed 5MB.',
            'document.mimes'                   => 'Document must be a PDF, JPG, or PNG file.',
        ];
    }
}
