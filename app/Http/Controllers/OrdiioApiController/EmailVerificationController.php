<?php

namespace App\Http\Controllers\OrdiioApiController;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\EmailVerificationRequest;

class EmailVerificationController extends Controller
{
    public function sendVerificationEmail(Request $request)
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'status' => true,
                'message' => 'Email already verified'
            ]);
        }

        $user->sendEmailVerificationNotification();

        return response()->json([
            'status' => true,
            'message' => 'Verification email sent'
        ]);
    }

    public function verify(EmailVerificationRequest $request)
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'status' => true,
                'message' => 'Email already verified'
            ]);
        }

        $user->markEmailAsVerified();

        return response()->json([
            'status' => true,
            'message' => 'Email verified successfully'
        ]);
    }
}
