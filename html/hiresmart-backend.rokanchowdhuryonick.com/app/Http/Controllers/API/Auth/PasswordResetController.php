<?php

namespace App\Http\Controllers\API\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Passwords\PasswordBroker;
use Illuminate\Support\Str;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class PasswordResetController extends Controller
{
    /**
     * Send password reset link to user's email
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'The provided email address is not found.',
                'errors' => $validator->errors()
            ], 422);
        }

        // Generate a token
        // $token = Str::random(60);
        $token = app(PasswordBroker::class)->createToken(User::where('email', $request->email)->first());
        // dd($token);
        // Store the token in password_reset_tokens table
        // DB::table('password_reset_tokens')->updateOrInsert(
        //     ['email' => $request->email],
        //     [
        //         'email' => $request->email,
        //         'token' => Hash::make($token),
        //         'created_at' => now()
        //     ]
        // );

        // Log the email content
        Log::info('Password Reset Email', [
            'to' => $request->email,
            'subject' => 'Reset Your Password',
            'message' => 'You have requested a password reset. Use the following token to reset your password.',
            'reset_token' => $token,
            'api_endpoint' => 'POST /api/auth/reset-password',
            'expires_in' => '60 minutes'
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Password reset instructions have been sent to your email.',
            'debug_info' => [
                'email' => $request->email,
                'reset_token' => $token,
                'api_endpoint' => 'POST /api/auth/reset-password',
                'expires_in' => '60 minutes',
                'required_fields' => [
                    'email' => $request->email,
                    'token' => $token,
                    'password' => 'your_new_password',
                    'password_confirmation' => 'your_new_password'
                ]
            ]
        ]);
    }

    /**
     * Reset user password using token
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required',
            'email' => 'required|email|exists:users,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Reset the password
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->setRememberToken(Str::random(60));

                $user->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json([
                'status' => 'success',
                'message' => 'Password has been reset successfully'
            ]);
        }

        return response()->json([
            'status' => 'error',
            'message' => $this->getResetErrorMessage($status)
        ], 400);
    }

    /**
     * Get error message for password reset status
     *
     * @param string $status
     * @return string
     */
    private function getResetErrorMessage($status)
    {
        return match($status) {
            Password::INVALID_TOKEN => 'Invalid or expired password reset token',
            Password::INVALID_USER => 'Invalid email address',
            default => 'Unable to reset password'
        };
    }
}
