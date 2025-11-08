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
        DB::beginTransaction();
        try {
                $validator = Validator::make($request->all(), [
                    'name'              => 'required|string|max:255',
                    'business_name'     => 'required|string|max:255',
                    'business_location' => 'required|string|max:255',
                    'phone_number'      => 'required|string|max:20',
                    'email'             => 'required|email|unique:clients,email',
                    'password'          => 'required|string|min:8',
                    'security_question' => 'required',
                    'security_answer'   => 'required',
                ]);

            if ($validator->fails()) {
                    DB::rollback();
                    
                    $errors = $validator->errors()->all();
                    $errorsString = implode(', ', $errors);
                    return response()->json(['status'  => false, 'message' => $errorsString, 'errors'  => $validator->errors()], 422);  
                }

            $user = Client::create([
                'name'              => $request->name,
                'business_name'     => $request->business_name,
                'business_location' => $request->business_location,
                'phone_number'      => $request->phone_number,
                'email'             => $request->email,
                'password'          => Hash::make($request->password),
                'security_question' => $request->security_question,
                'security_answer'   => $request->security_answer,
            ]);
            DB::commit();
            return response()->json(['status' => true, 'data' => $user, 'message' => 'Registration Successfully'], 200);
        } 
            catch (\Exception $exception) {
            DB::rollback();
            return response()->json(['status' => false, 'message' => 'Oops!!!, something went wrong, please try again.', 'errors' => $exception->getMessage()],500);
            
        } catch (\Throwable $exception) {
            DB::rollback();
            return response()->json(['status' => false, 'message' => 'Oops!!!, something went wrong, please try again.', 'errors' => $exception->getMessage()],500);
        }
    }

 
public function login(Request $request)
    {    
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string|min:8',
        ]);
        $client = Client::where('email', $request->email)->first();
        if (!$client || !Hash::check($request->password, $client->password)) {
            return response()->json(['status'  => false, 'message' => 'Invalid credentials'], 422);
            }
        $token = $client->createToken('auth_token')->plainTextToken;
        return response()->json(['status'   => true,
            'data'     => $client,
            'token'     => $token,
            'message'  => 'Login Successfully',
            ], 200);
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