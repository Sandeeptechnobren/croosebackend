<?php

namespace App\Http\Controllers\API;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Client;
use Postmark\PostmarkClient;
use App\Mail\SendOtpMail;
use Illuminate\Support\Facades\Validator;
use Twilio\Rest\Client as TwilioClient;
use Laravel\Sanctum\PersonalAccessToken;
use App\Models\OrdiioUser;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use App\Mail\ResetPasswordMail;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        try {
    
            // helper
            $has = fn($k) =>
                $request->has($k) &&
                (is_array($request->$k) ? !empty($request->$k) : trim((string)$request->$k) !== '');
    
            // normalize business_location
            $businessLocation = $request->has('business_location')
                ? (is_array($request->business_location)
                    ? ($request->business_location['name'] ?? null)
                    : $request->business_location)
                : null;
    
            /* ===============================
               STEP 1: SEND OTP
            =============================== */
            if (
                $has('name') &&
                $has('business_name') &&
                $has('business_location') &&
                $has('phone_number') &&
                $has('email') &&
                !$has('otp') &&
                !$has('password')
            ) {
    
                $request->validate([
                    'name'          => 'required|string|max:255',
                    'business_name' => 'required|string|max:255',
                    'phone_number'  => 'required|string|max:20',
                    'email'         => 'required|email|unique:clients,email',
                ]);
    
                if (!$businessLocation) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Business location is required'
                    ], 422);
                }
    
                $otp = rand(100000, 999999);
    
                DB::table('otp_codes')->where('email', $request->email)->delete();

               DB::table('otp_codes')->insert([
               'email'      => $request->email,
               'otp'        => (string) $otp,
               'created_at' => now(),
              ]);
                Http::post('https://apiadmin.schoolexl.com/index.php/api/v2/auth/send-otp',
                [
                    'type'     => 'registration',
                    'app_name' => 'Croose',
                    'name'     => $request->email,
                    'email'    => $request->email,
                    'otp'      => (string) $otp,
                ]
            );
            DB::commit();
            return response()->json([
                'status'  => true,
                'step'    => 'otp',
                'message' => 'OTP sent to email',
            ]);
                return response()->json([
                    'status'  => true,
                    'message' => 'OTP sent successfully',
                    'email'   => $request->email,
                    'otp'     => $otp // ❌ remove in production
                ]);
            }
    
            /* ===============================
               STEP 2: VERIFY OTP
            =============================== */
            if ($has('email') && $has('otp') && !$has('password')) {
    
                $request->validate([
                    'email' => 'required|email',
                    'otp'   => 'required|digits:6',
                ]);
    
                $otpRow = DB::table('otp_codes')
                    ->where('email', $request->email)
                    ->where('otp', $request->otp)
                    ->first();
    
                if (!$otpRow) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Invalid OTP'
                    ], 422);
                }
    
                return response()->json([
                    'status'  => true,
                    'message' => 'OTP verified successfully'
                ]);
            }
    
            /* ===============================
               STEP 3: FINAL REGISTER
            =============================== */
            if (
                $has('name') &&
                $has('business_name') &&
                $has('business_location') &&
                $has('phone_number') &&
                $has('email') &&
                $has('password')
            ) {
    
                $request->validate([
                    'name'          => 'required|string|max:255',
                    'business_name' => 'required|string|max:255',
                    'phone_number'  => 'required|string|max:20',
                    'email'         => 'required|email|unique:clients,email',
                    'password'      => 'required|min:8',
                ]);
    
                if (!$businessLocation) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Business location is required'
                    ], 422);
                }
    
                // OTP must exist (verified earlier)
                $otpExists = DB::table('otp_codes')
                    ->where('email', $request->email)
                    ->exists();
    
                if (!$otpExists) {
                    return response()->json([
                        'status' => false,
                        'message' => 'OTP verification required'
                    ], 403);
                }
    
                $client = Client::create([
                    'name'              => $request->name,
                    'business_name'     => $request->business_name,
                    'business_location' => $businessLocation,
                    'phone_number'      => $request->phone_number,
                    'email'             => $request->email,
                    'password'          => Hash::make($request->password),
                    'email_verified_at' => now(),
                ]);
    
                DB::table('otp_codes')->where('email', $request->email)->delete();
    
                return response()->json([
                    'status'  => true,
                    'message' => 'Registration completed successfully',
                    'data'    => $client
                ]);
            }
    
            return response()->json([
                'status' => false,
                'message' => 'Invalid request payload'
            ], 400);
    
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Registration failed',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

public function login(Request $request)
{
    $request->validate([
        'email'    => 'required|email',
        'password' => 'required|string|min:8',
    ]);

    $client = Client::where('email', $request->email)->first();

    if (!$client) {
        return response()->json([
            'status' => false,
            'field'  => 'email',
            'message'=> 'Email not registered'
        ], 422);
    }

    if (!Hash::check($request->password, $client->password)) {
        return response()->json([
            'status' => false,
            'field'  => 'password',
            'message'=> 'Incorrect password'
        ], 422);
    }

    return response()->json([
        'status'  => true,
        'data'    => $client,
        'token'   => $client->createToken('auth_token')->plainTextToken,
        'message' => 'Login Successfully',
    ], 200);
}


    public function resetPassword(Request $request)
    {
        try {
            Log::info('RESET PASSWORD API HIT', $request->only('email', 'otp'));
    
            /**
             * --------------------------------------------------
             * STEP 1: SEND OTP (email only)
             * --------------------------------------------------
             */
            if ($request->filled('email') && !$request->filled('otp')) {
    
                $client = Client::where('email', $request->email)->first();
    
                if (!$client) {
                    return response()->json([
                        'status'  => false,
                        'message' => 'Email not registered'
                    ], 404);
                }
    
                // Generate OTP
                $otp = rand(100000, 999999);
    
                // Remove any old reset requests
                DB::table('password_resets')
                    ->where('email', $request->email)
                    ->delete();
    
                // Store new OTP
                DB::table('password_resets')->insert([
                    'email'      => $request->email,
                    'otp'        => $otp,
                    'token'      => null,
                    'created_at'=> now(),
                    'updated_at'=> now(),
                ]);
    
                // Send OTP via external service
                Http::post('https://apiadmin.schoolexl.com/index.php/api/v2/auth/send-otp', [
                    'type'     => 'reset',
                    'app_name' => 'Croose',
                    'name'     => $client->email,
                    'email'    => $client->email,
                    'otp'      => (string) $otp,
                ]);
    
                Log::info('RESET PASSWORD OTP SENT', ['email' => $request->email]);
    
                return response()->json([
                    'status'  => true,
                    'step'    => 'otp',
                    'message' => 'OTP sent successfully',
                    'data'    => [
                        'email' => $request->email,
                        // ⚠️ Dev/testing only — remove in production
                        'otp'   => $otp
                    ]
                ]);
            }
    
            /**
             * --------------------------------------------------
             * STEP 2: VERIFY OTP
             * --------------------------------------------------
             */
            if ($request->filled('email') && $request->filled('otp') && !$request->filled('new_password')) {
    
                $record = DB::table('password_resets')
                    ->where('email', $request->email)
                    ->where('otp', $request->otp)
                    ->first();
    
                if (!$record) {
                    return response()->json([
                        'status'  => false,
                        'message' => 'Invalid OTP'
                    ], 422);
                }
    
                Log::info('RESET PASSWORD OTP VERIFIED', ['email' => $request->email]);
    
                return response()->json([
                    'status'  => true,
                    'step'    => 'password',
                    'message' => 'OTP verified successfully'
                ]);
            }
    
            /**
             * --------------------------------------------------
             * STEP 3: RESET PASSWORD (OTP + NEW PASSWORD)
             * --------------------------------------------------
             */
            if ($request->filled('email') && $request->filled('otp') && $request->filled('new_password')) {
    
                $client = Client::where('email', $request->email)->first();
    
                if (!$client) {
                    return response()->json([
                        'status'  => false,
                        'message' => 'User not found'
                    ], 404);
                }
    
                // Validate OTP again (extra safety)
                $record = DB::table('password_resets')
                    ->where('email', $request->email)
                    ->where('otp', $request->otp)
                    ->first();
    
                if (!$record) {
                    return response()->json([
                        'status'  => false,
                        'message' => 'Invalid or expired OTP'
                    ], 422);
                }
    
                // Update password
                $client->update([
                    'password'        => Hash::make($request->new_password),
                    'remember_token'  => null,
                ]);
    
                // 🔐 Logout user from ALL devices (Sanctum)
                if (method_exists($client, 'tokens')) {
                    $client->tokens()->delete();
                }
    
                // Clear password reset record
                DB::table('password_resets')
                    ->where('email', $request->email)
                    ->delete();
    
                Log::info('RESET PASSWORD SUCCESSFUL - USER LOGGED OUT EVERYWHERE', [
                    'client_id' => $client->id
                ]);
    
                return response()->json([
                    'status'  => true,
                    'message' => 'Password reset successful. Please login again.'
                ]);
            }
    
            /**
             * --------------------------------------------------
             * INVALID REQUEST
             * --------------------------------------------------
             */
            return response()->json([
                'status'  => false,
                'message' => 'Invalid request'
            ], 400);
    
        } catch (\Exception $e) {
    
            Log::error('RESET PASSWORD ERROR', [
                'error' => $e->getMessage()
            ]);
    
            return response()->json([
                'status'  => false,
                'message' => 'Server error',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
    

 
public function logout(Request $request)
    {  
        $request->user()->currentAccessToken()->delete();
        return response()->json([ 'status'  => true, 'message' => 'Logged Out Successfully'],200);
    } 
public function sendOtpEmail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email'
        ]);
        if ($validator->fails()) { 
            return response()->json([
                'status' => true,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 200);
        }
        $email = $request->input('email');
        $otp   = rand(100000, 999999);
        DB::table('otp_codes')->updateOrInsert(
            ['email' => $email],
            ['otp' => $otp, 'created_at' => now()]
            );
        try {
            Mail::raw("Your OTP is: $otp", function ($message) use ($email) {
                $message->to($email)->subject('Your OTP Code');
            });
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to send OTP via Email.',
                'error' => $e->getMessage()
            ], 500);
        }   
            return response()->json([
                'status' => true,
                'message' => 'OTP sent to email successfully!'
            ], 200);
    }
   
public function index()
    {
        return response()->json([
            'status' => true,
            'users' => Client::all(),
            'message' =>'Client List'
        ]);
    }

// public function reset_password_viasecurity(Request $request)
//     {
//         try{
//             DB::beginTransaction();
//             $validated=$request->validate([
//                 'phone_number'      => 'required|string',
//                 'email'             => 'required|email',
//                 'security_question' => 'required',
//                 'security_answer'   => 'required',
//                 'password' => 'required|min:8',
//             ]);
//             $client=Client::where('phone_number',$validated['phone_number'])
//                 ->where('email',$validated['email'])
//                 ->where('security_question',$validated['security_question'])
//                 ->where('security_answer',$validated['security_answer'])
//                 ->first();
//             if(!$client){
//                 DB::rollback();
//                 return response()->json(['status'=>false,'message'=>'Client not found!'],404);
//             }
//             $client->update([
//                 'password'=>Hash::make($validated['password']),
//             ]);
//             DB::commit();
//             return response()->json([
//                 'status' => true,
//                 'message' => 'Password updated successfully',
//             ]);
            
//         }
//         catch(\Exception $e){
//             DB::rollBack();
//             return response()->json([
//                 'status'  => false,
//                 'message' => 'Something went wrong.',
//                 'error'   => $e->getMessage(),
//             ], 500);    
//         }
//     }
    public function reset_password_viasecurity(Request $request)
     {
        try {
            DB::beginTransaction();
            $validated = $request->validate([
                'phone_number'      => 'required|string',
                'email'             => 'required|email',
                'security_question' => 'required|string',
                'security_answer'   => 'required|string',
                'password'          => 'required|min:8|confirmed',
            ]);
            $client = Client::where('phone_number', $validated['phone_number'])
                ->where('email', $validated['email'])
                ->first();
            if (!$client) {
                DB::rollBack();
                return response()->json([
                    'status' => false,
                    'message' => 'Client not found with given phone and email.'
                ], 404);
            }
            if (
                $client->security_question !== $validated['security_question'] ||
                $client->security_answer !== $validated['security_answer']
            ) {
                DB::rollBack();
                return response()->json([
                    'status' => false,
                    'message' => 'Security question or answer is incorrect.'
                ], 403);
            }
            $client->update([
                'password' => Hash::make($validated['password']),
            ]);
            DB::commit();
            return response()->json([
                'status'  => true,
                'message' => 'Password updated successfully.',
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status'  => false,
                'message' => 'Something went wrong.',
                'error'   => $e->getMessage(),
            ], 500);
        }
     }
    
    public function find_account(Request $request, $email)
     {
        $client = Client::where('email', $email)->first();
        if ($client) {
            return response()->json([
                'status' => 200,
                'account_existing_status' => 1,
                'name' => $client->name,
                'message' => "Account exists"
            ]);
        } else {
            return response()->json([
                'status' => 404,
                'account_existing_status' => 0,
                'message' => "Account not found"
            ]);
        }
     }

    // public function forgot_password_ordiio(Request $request)
    //  {
    //         $request->validate(['email' => 'required|email']);

    //         $user = OrdiioUser::where('email', $request->email)->first();
    //         if (!$user) {
    //             return response()->json(['message' => 'User not found'], 404);
    //         }
 
    //         $token = Str::random(64);

    //         DB::table('password_reset_tokens')->updateOrInsert(
    //             ['email' => $request->email],
    //             ['token' => $token, 'created_at' => now()]
    //         );
 
    //         return response()->json(['message' => 'Reset token generated', 'token' => $token]);
    //  }

    //  public function reset_password_ordiio(Request $request)
    //   {
    //         $request->validate([
    //             'email' => 'required|email',
    //             'token' => 'required|string',
    //             'password' => 'required|string|min:6',
    //         ]);

    //         $reset = DB::table('password_reset_tokens')
    //             ->where('email', $request->email)
    //             ->where('token', $request->token)
    //             ->first();

    //         if (!$reset) {
    //             return response()->json(['message' => 'Invalid token'], 400);
    //         }

    //         OrdiioUser::where('email', $request->email)->update([
    //             'password' => bcrypt($request->password),
    //         ]);

    //         // Delete used token
    //         DB::table('password_reset_tokens')->where('email', $request->email)->delete();

    //         return response()->json(['message' => 'Password reset successful']);
    //   }
     
    public function forgot_password_ordiio(Request $request)
     {
            $request->validate(['email' => 'required|email']);

            $user = OrdiioUser::where('email', $request->email)->first();
            if (!$user) {
                return response()->json(['message' => 'User not found'], 404);
            }

            $token = Str::random(64);

            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $request->email],
                ['token' => $token, 'created_at' => now()]
            );
 
            $resetLink = 'https://app.ordiio.com/reset-password?token=' . $token . '&email=' . $request->email;

            $response = Http::withHeaders([
                    'Accept' => 'application/json',
                ])->post('https://apiadmin.schoolexl.com/index.php/api/v2/ordiio/reset-password', [
                    'link' => $resetLink,
                    'email' => $request->email,
                    'name'  => $user->name ?? 'User',
                ]);

            // Mail::to($request->email)->send(new ResetPasswordMail($resetLink));

            return response()->json(['message' => 'Reset link sent to your email']);
     }
 
     public function reset_password_ordiio(Request $request)
      {
            $request->validate([
                'email' => 'required|email',
                'token' => 'required|string',
                'password' => 'required|string|min:6',
            ]);

            $reset = DB::table('password_reset_tokens')
                ->where('email', $request->email)
                ->where('token', $request->token)
                ->first();

            if (!$reset) {
                return response()->json(['message' => 'Invalid token'], 400);
            }

            // Optional: check if token is expired (e.g., older than 1 hour)
            if (Carbon::parse($reset->created_at)->addHour()->isPast()) {
                return response()->json(['message' => 'Token expired'], 400);
            }

            OrdiioUser::where('email', $request->email)->update([
                'password' => bcrypt($request->password),
            ]);

            // Delete used token
            DB::table('password_reset_tokens')->where('email', $request->email)->delete();

            return response()->json(['message' => 'Password reset successful']);
      }


}