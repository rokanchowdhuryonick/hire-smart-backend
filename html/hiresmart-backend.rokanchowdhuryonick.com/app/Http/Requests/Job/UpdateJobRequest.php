<?php

namespace App\Http\Requests\Job;

use Illuminate\Foundation\Http\FormRequest;

class UpdateJobRequest extends FormRequest
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
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'min_salary' => 'nullable|numeric|min:0',
            'max_salary' => 'nullable|numeric|min:0|gte:min_salary',
            'currency' => 'nullable|string|max:3',
            'employment_type' => 'sometimes|in:full_time,part_time,contract,internship',
            'status' => 'sometimes|in:active,closed,draft,archived',
            'deadline' => 'nullable|date|after:today',
            'experience_years' => 'nullable|integer|min:0|max:50',
            'country_id' => 'sometimes|exists:countries,id',
            'state_id' => 'nullable|exists:states,id',
            'city_id' => 'nullable|exists:cities,id',
            'area_id' => 'nullable|exists:areas,id',
            'skills' => 'nullable|array',
            'skills.*.id' => 'required|exists:skills,id',
            'skills.*.is_required' => 'boolean',
            'company' => 'nullable|array',
            'company.name' => 'nullable|string|max:255',
            'company.description' => 'nullable|string',
            'company.website' => 'nullable|url',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'employment_type.in' => 'Employment type must be full-time, part-time, contract, or internship',
            'deadline.after' => 'Application deadline must be after today',
            'max_salary.gte' => 'Maximum salary must be greater than or equal to minimum salary',
            'experience_years.max' => 'Experience years cannot exceed 50',
            'country_id.exists' => 'Please select a valid country',
            'skills.*.id.exists' => 'One or more selected skills are invalid',
            'company.website.url' => 'Company website must be a valid URL',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'title' => 'job title',
            'description' => 'job description',
            'min_salary' => 'minimum salary',
            'max_salary' => 'maximum salary',
            'employment_type' => 'employment type',
            'deadline' => 'application deadline',
            'experience_years' => 'required experience',
            'country_id' => 'country',
            'state_id' => 'state/province',
            'city_id' => 'city',
            'area_id' => 'area',
            'company.name' => 'company name',
            'company.description' => 'company description',
            'company.website' => 'company website',
        ];
    }
} 