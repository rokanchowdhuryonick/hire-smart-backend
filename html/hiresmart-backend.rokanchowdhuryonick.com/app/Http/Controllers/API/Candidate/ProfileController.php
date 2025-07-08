<?php

namespace App\Http\Controllers\API\Candidate;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\File;

class ProfileController extends Controller
{
    /**
     * Upload resume to candidate profile
     */
    public function uploadResume(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'resume' => [
                'required',
                'file',
                File::types(['pdf', 'doc', 'docx'])
                    ->max(5 * 1024) // 5MB max
                    ->min(10), // 10KB min
            ],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $user = Auth::user();
            $file = $request->file('resume');
            
            // Generate unique filename
            $fileName = 'resume_' . $user->id . '_' . time() . '.' . $file->getClientOriginalExtension();
            
            // Delete old resume if exists
            if ($user->profile && $user->profile->resume_path) {
                Storage::disk('resumes')->delete(basename($user->profile->resume_path));
            }
            
            // Store new resume
            $path = $file->storeAs('', $fileName, 'resumes');
            $fullUrl = Storage::disk('resumes')->url($path);
            
            // Update user profile
            $user->profile()->updateOrCreate(
                ['user_id' => $user->id],
                ['resume_path' => $fullUrl]
            );

            return response()->json([
                'success' => true,
                'message' => 'Resume uploaded successfully',
                'data' => [
                    'resume_url' => $fullUrl,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload resume',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get current resume info
     */
    public function getResume(): JsonResponse
    {
        $user = Auth::user();
        $profile = $user->profile;

        if (!$profile || !$profile->resume_path) {
            return response()->json([
                'success' => false,
                'message' => 'No resume found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'resume_url' => $profile->resume_path,
                'uploaded_at' => $profile->updated_at,
            ],
        ]);
    }

    /**
     * Delete current resume
     */
    public function deleteResume(): JsonResponse
    {
        $user = Auth::user();
        $profile = $user->profile;

        if (!$profile || !$profile->resume_path) {
            return response()->json([
                'success' => false,
                'message' => 'No resume found to delete',
            ], 404);
        }

        try {
            // Delete file from storage
            Storage::disk('resumes')->delete(basename($profile->resume_path));
            
            // Update profile
            $profile->update(['resume_path' => null]);

            return response()->json([
                'success' => true,
                'message' => 'Resume deleted successfully',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete resume',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
} 