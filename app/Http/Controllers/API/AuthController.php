<?php

namespace App\Http\Controllers\API;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Client;
use App\Mail\SendOtpMail;

use Illuminate\Support\Facades\Validator;
use Twilio\Rest\Client as TwilioClient;


class AuthController extends Controller
{
    // ✅ Register
    public function register(Request $request)
    {
        $request->validate([
            'name'              => 'required|string|max:255',
            'business_name'     => 'required|string|max:255',
            'business_location' => 'required|string|max:255',
            'email'             => 'required|email|unique:clients,email',
            'password'          => 'required|string|min:8',
        ]);

        $user = Client::create([
            'name'              => $request->name,
            'business_name'     => $request->business_name,
            'business_location' => $request->business_location,
            'email'             => $request->email,
            'password'          => Hash::make($request->password),
        ]);

        $token = $user->createToken('authToken')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user'  => $user
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
            'clients' => $user,
            'message' => 'Login successful'
        ], Response::HTTP_OK);
    }

    // ✅ Logout
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }

    // ✅ Send OTP via Email and WhatsApp
//     public function sendOtp(Request $request)
//     {
//         $request->validate([
//             'email' => 'required|email|exists:clients,email',
//             'phone' => 'required' // 🟡 Make sure phone is sent in the request
//         ]);

//         $otp = rand(100000, 999999);

//         DB::table('otp_codes')->updateOrInsert(
//             ['email' => $request->email],
//             ['otp' => $otp, 'created_at' => now()]
//         );

//         // ✅ Send Email
//         Mail::raw("Your OTP is: $otp", function ($message) use ($request) {
//             $message->to($request->email)
//                     ->subject('Your OTP Code');
//         });

//         // ✅ Send WhatsApp
       
       

//         return response()->json(['message' => 'OTP sent to your email and WhatsApp.'], 200);
//     }

//     // ✅ WhatsApp Sending Function


// public function sendOtp(Request $request)
// {
//     $validator = Validator::make($request->all(), [
//         'phone' => 'required|string', // Format: +91XXXXXXXXXX
//     ]);
    


//     if ($validator->fails()) {
//         return response()->json(['error' => $validator->errors()], 422);
//     }

//     $phone = $request->input('phone');
    
//     $otp = rand(100000, 999999);

//     // ✅ Save OTP to database (optional, for later verification)
//     // DB::table('otp_codes')->updateOrInsert(
//     //     ['phone' => $phone],
//     //     ['otp' => $otp, 'created_at' => now()]
//     // );

//     // ✅ Prepare Twilio Client
//     $sid = env('TWILIO_SID');
//     $token = env('TWILIO_AUTH_TOKEN');
//     $from = 'whatsapp:' . env('TWILIO_WHATSAPP_NUMBER');
//     $to = 'whatsapp:' . $phone;

//     try {
//         $twilio = new TwilioClient($sid, $token);

//         $twilio->messages->create($to, [
//             'from' => $from,
//             'body' => "Your Croose OTP is: {$otp}"
//         ]);

//         return response()->json([
//             'success' => true,
//             'message' => 'OTP sent to WhatsApp successfully!'
//         ]);
//     } catch (\Exception $e) {
//         return response()->json([
//             'success' => false,
//             'error' => $e->getMessage()
//         ], 500);
//     }
// }

        public function sendOtp(Request $request)
            {
                // ✅ Validate request
                $validator = Validator::make($request->all(), [
                    'email' => 'required|email|exists:clients,email',
                    'phone' => 'required|string', // Format: +91XXXXXXXXXX
                ]);

                if ($validator->fails()) {
                    return response()->json(['error' => $validator->errors()], 422);
                }

                $email = $request->input('email');
                $phone = $request->input('phone');
                $otp = rand(100000, 999999);

                // ✅ Store OTP in DB (linked to email)
                DB::table('otp_codes')->updateOrInsert(
                    ['email' => $email],
                    ['otp' => $otp, 'created_at' => now()]
                );

                // ✅ Send OTP via Email
                Mail::raw("Your OTP is: $otp", function ($message) use ($email) {
                    $message->to($email)->subject('Your OTP Code');
                });

                // ✅ Send OTP via WhatsApp
                $sid = env('TWILIO_SID');
                $token = env('TWILIO_AUTH_TOKEN');
                $from = 'whatsapp:' . env('TWILIO_WHATSAPP_NUMBER');
                $to = 'whatsapp:' . $phone;

                try {
                    $twilio = new TwilioClient($sid, $token);

                    $twilio->messages->create($to, [
                        'from' => $from,
                        'body' => "Your Croose OTP is: {$otp}"
                    ]);
                } catch (\Exception $e) {
                    \Log::error("WhatsApp OTP Error: " . $e->getMessage());
                    return response()->json([
                        'success' => false,
                        'message' => 'OTP sent to email, but WhatsApp failed.',
                        'error'   => $e->getMessage()
                    ], 500);
                }

                return response()->json([
                    'success' => true,
                    'message' => 'OTP sent to both email and WhatsApp successfully!'
                ]);
            }


    

    public function index()
    {
        return response()->json(User::all());
    }
}
