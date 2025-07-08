<?php

namespace App\Http\Requests\Application;

use Illuminate\Foundation\Http\FormRequest;

class ApplyJobRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // User must be authenticated as candidate via middleware
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'cover_letter' => 'sometimes|string|max:2000',
            'resume_path' => 'sometimes|string|max:255',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'cover_letter.max' => 'Cover letter cannot exceed 2000 characters',
            'resume_path.max' => 'Resume file path is too long',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'cover_letter' => 'cover letter',
            'resume_path' => 'resume file',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Trim whitespace from cover letter
        if ($this->has('cover_letter')) {
            $this->merge([
                'cover_letter' => trim($this->cover_letter),
            ]);
        }
    }
} 