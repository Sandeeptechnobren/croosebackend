<?php

namespace App\Http\Controllers\API;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Models\User;
use App\Models\clients;
use Illuminate\Http\Response;
use App\Mail\SendOtpMail;
use App\Models\Client;

class AuthController extends Controller
{
    // ✅ Register
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'business_name' => 'required|string|max:255',
            'business_location' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:8',
        ]);

        $user = Client::create([
            'name' => $request->name,
            'business_name' => $request->business_name,
            'business_location' => $request->business_location,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $token = $user->createToken('authToken')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $user
        ], 200);
    }

    // ✅ Login
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required'
        ]);
        

        $user = Client::where('email', $request->email)->first();
       

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Invalid credentials',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $token = $user->createToken('authToken')->plainTextToken;

        return response()->json([
            'token'   => $token,
            'clients'    => $user,
            'message' => 'Login successful'
        ], Response::HTTP_OK);
    }

    // ✅ Logout
        public function logout(Request $request)
        {
            $request->user()->currentAccessToken()->delete();

            return response()->json(['message' => 'Logged out successfully']);
        }

    // ✅ Send OTP to Email for Forgot Password
       
       public function sendOtp(Request $request)
            {
                $request->validate([
                    'email' => 'required|email|exists:clients,email',
                ]);

                $otp = rand(100000, 999999);

                DB::table('otp_codes')->updateOrInsert(
                    ['email' => $request->email],
                    ['otp' => $otp, 'created_at' => now()]
                );

                Mail::raw("Your OTP is: $otp", function ($message) use ($request) {
                    $message->to($request->email)
                            ->subject('Your OTP Code');
                });

                return response()->json(['message' => 'OTP sent to your email.'], 200);
            }


    // ✅ Verify OTP and Reset Password
    // public function verifyOtpAndResetPassword(Request $request)
    // {
    //     $request->validate([
    //         'email' => 'required|email|exists:users,email',
    //         'otp' => 'required',
    //         'password' => 'required|string|min:8|confirmed',
    //     ]);

    //     $otpData = DB::table('otp_codes')
    //         ->where('email', $request->email)
    //         ->where('otp', $request->otp)
    //         ->where('created_at', '>=', now()->subMinutes(10)) // Expiry check
    //         ->first();

    //     if (!$otpData) {
    //         return response()->json(['message' => 'Invalid or expired OTP'], 400);
    //     }

    //     // Reset password
    //     $user = User::where('email', $request->email)->first();
    //     $user->password = Hash::make($request->password);
    //     $user->save();

    //     // OTP हटाएँ
    //     DB::table('otp_codes')->where('email', $request->email)->delete();

    //     return response()->json(['message' => 'Password reset successful'], 200);
    // }

    // ✅ All Users (optional)
    public function index()
    {
        return response()->json(User::all());
    }
}