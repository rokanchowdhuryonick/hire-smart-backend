<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Tymon\JWTAuth\Facades\JWTAuth;

class UpdateProfileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // User must be authenticated via middleware
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $user = JWTAuth::user();
        
        $rules = [
            // Basic user data
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $user->id,
        ];

        // Profile data for candidates only
        if ($user && $user->isCandidate()) {
            $rules = array_merge($rules, [
                'profile.bio' => 'sometimes|string',
                'profile.phone' => 'sometimes|string|max:20',
                'profile.min_salary' => 'sometimes|numeric|min:0',
                'profile.max_salary' => 'sometimes|numeric|min:0|gte:profile.min_salary',
                'profile.currency' => 'sometimes|string|max:3',
                'profile.country_id' => 'sometimes|exists:countries,id',
                'profile.state_id' => 'sometimes|exists:states,id',
                'profile.city_id' => 'sometimes|exists:cities,id',
                'profile.area_id' => 'sometimes|exists:areas,id',
            ]);
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'email.unique' => 'This email address is already in use by another account',
            'profile.max_salary.gte' => 'Maximum salary must be greater than or equal to minimum salary',
            'profile.phone.max' => 'Phone number cannot exceed 20 characters',
            'profile.currency.max' => 'Currency code must be 3 characters (e.g., USD, EUR)',
            'profile.country_id.exists' => 'Please select a valid country',
            'profile.state_id.exists' => 'Please select a valid state/province',
            'profile.city_id.exists' => 'Please select a valid city',
            'profile.area_id.exists' => 'Please select a valid area',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'name' => 'full name',
            'email' => 'email address',
            'profile.bio' => 'biography',
            'profile.phone' => 'phone number',
            'profile.min_salary' => 'minimum salary',
            'profile.max_salary' => 'maximum salary',
            'profile.currency' => 'currency',
            'profile.country_id' => 'country',
            'profile.state_id' => 'state/province',
            'profile.city_id' => 'city',
            'profile.area_id' => 'area',
        ];
    }
} 