<?php

namespace App\Http\Requests\Application;

use Illuminate\Foundation\Http\FormRequest;

class BulkUpdateApplicationsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // User must be authenticated as employer via middleware
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'application_ids' => 'required|array|min:1',
            'application_ids.*' => 'integer|exists:applications,id',
            'status' => 'required|in:pending,reviewed,shortlisted,rejected,hired',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'application_ids.required' => 'Please select at least one application',
            'application_ids.array' => 'Application IDs must be provided as an array',
            'application_ids.min' => 'Please select at least one application',
            'application_ids.*.integer' => 'Application IDs must be valid integers',
            'application_ids.*.exists' => 'One or more selected applications do not exist',
            'status.required' => 'Application status is required',
            'status.in' => 'Status must be one of: pending, reviewed, shortlisted, rejected, or hired',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'application_ids' => 'applications',
            'status' => 'application status',
        ];
    }
} 