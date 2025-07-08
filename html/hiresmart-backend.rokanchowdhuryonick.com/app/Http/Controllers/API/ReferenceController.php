<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Skill;
use App\Models\Country;
use App\Models\State;
use App\Models\City;
use App\Models\Area;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class ReferenceController extends Controller
{
    /**
     * Get all skills
     */
    public function skills(): JsonResponse
    {
        $skills = Cache::remember('reference.skills', 3600, function () {
            return Skill::select('id', 'name')
                ->orderBy('name')
                ->get();
        });

        return response()->json([
            'status' => 'success',
            'data' => $skills,
            'meta' => [
                'total' => $skills->count(),
                'note' => 'Use skill IDs when creating job postings or updating profiles'
            ]
        ]);
    }

    /**
     * Get all countries
     */
    public function countries(): JsonResponse
    {
        $countries = Cache::remember('reference.countries', 3600, function () {
            return Country::select('id', 'name')
                ->orderBy('name')
                ->get();
        });

        return response()->json([
            'status' => 'success',
            'data' => $countries,
            'meta' => [
                'total' => $countries->count(),
                'note' => 'Use country_id to get states for that country'
            ]
        ]);
    }

    /**
     * Get states by country
     */
    public function states(Request $request): JsonResponse
    {
        $countryId = $request->get('country_id');
        
        if (!$countryId) {
            return response()->json([
                'status' => 'error',
                'message' => 'country_id parameter is required'
            ], 422);
        }

        $cacheKey = "reference.states.country_{$countryId}";
        $states = Cache::remember($cacheKey, 3600, function () use ($countryId) {
            return State::select('id', 'name', 'country_id')
                ->where('country_id', $countryId)
                ->orderBy('name')
                ->get();
        });

        return response()->json([
            'status' => 'success',
            'data' => $states,
            'meta' => [
                'total' => $states->count(),
                'country_id' => (int) $countryId,
                'note' => 'Use state_id to get cities for that state'
            ]
        ]);
    }

    /**
     * Get cities by state
     */
    public function cities(Request $request): JsonResponse
    {
        $stateId = $request->get('state_id');
        
        if (!$stateId) {
            return response()->json([
                'status' => 'error',
                'message' => 'state_id parameter is required'
            ], 422);
        }

        $cacheKey = "reference.cities.state_{$stateId}";
        $cities = Cache::remember($cacheKey, 3600, function () use ($stateId) {
            return City::select('id', 'name', 'state_id')
                ->where('state_id', $stateId)
                ->orderBy('name')
                ->get();
        });

        return response()->json([
            'status' => 'success',
            'data' => $cities,
            'meta' => [
                'total' => $cities->count(),
                'state_id' => (int) $stateId,
                'note' => 'Use city_id to get areas for that city'
            ]
        ]);
    }

    /**
     * Get areas by city
     */
    public function areas(Request $request): JsonResponse
    {
        $cityId = $request->get('city_id');
        
        if (!$cityId) {
            return response()->json([
                'status' => 'error',
                'message' => 'city_id parameter is required'
            ], 422);
        }

        $cacheKey = "reference.areas.city_{$cityId}";
        $areas = Cache::remember($cacheKey, 3600, function () use ($cityId) {
            return Area::select('id', 'name', 'city_id', 'state_id', 'country_id')
                ->where('city_id', $cityId)
                ->orderBy('name')
                ->get();
        });

        return response()->json([
            'status' => 'success',
            'data' => $areas,
            'meta' => [
                'total' => $areas->count(),
                'city_id' => (int) $cityId,
                'note' => 'Use area_id in job postings and profiles for specific location'
            ]
        ]);
    }

    /**
     * Get employment types
     */
    public function employmentTypes(): JsonResponse
    {
        $types = [
            ['value' => 'full_time', 'label' => 'Full Time'],
            ['value' => 'part_time', 'label' => 'Part Time'], 
            ['value' => 'contract', 'label' => 'Contract'],
            ['value' => 'internship', 'label' => 'Internship'],
        ];

        return response()->json([
            'status' => 'success',
            'data' => $types,
            'meta' => [
                'note' => 'Use these values for employment_type field in job postings'
            ]
        ]);
    }

    /**
     * Get job statuses
     */
    public function jobStatuses(): JsonResponse
    {
        $statuses = [
            ['value' => 'active', 'label' => 'Active'],
            ['value' => 'draft', 'label' => 'Draft'],
            ['value' => 'closed', 'label' => 'Closed'],
            ['value' => 'archived', 'label' => 'Archived'],
        ];

        return response()->json([
            'status' => 'success',
            'data' => $statuses,
            'meta' => [
                'note' => 'Use these values for status field in job postings'
            ]
        ]);
    }

    /**
     * Get all reference data in one call
     */
    public function all(): JsonResponse
    {
        $data = Cache::remember('reference.all', 1800, function () {
            return [
                'skills' => Skill::select('id', 'name')->orderBy('name')->get(),
                'countries' => Country::select('id', 'name')->orderBy('name')->get(),
                'employment_types' => [
                    ['value' => 'full_time', 'label' => 'Full Time'],
                    ['value' => 'part_time', 'label' => 'Part Time'], 
                    ['value' => 'contract', 'label' => 'Contract'],
                    ['value' => 'internship', 'label' => 'Internship'],
                ],
                'job_statuses' => [
                    ['value' => 'active', 'label' => 'Active'],
                    ['value' => 'draft', 'label' => 'Draft'],
                    ['value' => 'closed', 'label' => 'Closed'],
                    ['value' => 'archived', 'label' => 'Archived'],
                ]
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => $data,
            'meta' => [
                'note' => 'All reference data. Use individual endpoints for location hierarchy (states/cities/areas)'
            ]
        ]);
    }
} 