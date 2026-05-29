<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\DelayDogUserDetail;
use App\Models\DelayDogJourney;
use App\Models\DelayDogClaims;
use App\Models\DelayDogTickets;
use Illuminate\Support\Str;

class DelaydogController extends Controller
{
    public function delaydogusers(Request $request, $user_phone)
        {
            $phone_number=$user_phone;
            try 
            {
                DB::beginTransaction();
                $validated = $request->validate([
                    'full_name'            => 'required|string|max:100',
                    'email'                => 'required|email|max:150|unique:delay_dog_user_details,email',
                    'is_monthly_railcard'  => 'required|boolean',
                    'railcard_image_path'  => 'nullable|string|max:255',
                    'usual_origin'         => 'required|string|max:100',
                    'usual_destination'    => 'required|string|max:100',
                    'registered_at'        => 'nullable|date',
                    'last_daily_check'     => 'nullable|date',
                ]);
                $validated['phone_number'] = $phone_number;

                $imagePath = null;
                if ($request->hasFile('railcard_image_path')) {
                    $image = $request->file('railcard_image_path');
                    if ($image->isValid()) {
                        $imageName = time() . '.' . $image->getClientOriginalExtension();
                        $imagePath = $image->storeAs('services', $imageName, 'public');
                    }
                }
                $delay_dog_user = DelayDogUserDetail::create([
                    ...$validated,
                    'railcard_image_path' => $imagePath,
                ]);
                DB::commit();
                return response()->json($delay_dog_user);
            } 
            catch (\Exception $e) {
                DB::rollBack();
                return response()->json(['error' => $e->getMessage()], 500);
            }
        }
    public function delaydogjourney(Request $request, $user_phone) {
        try {
            DB::beginTransaction();

            $user = DelayDogUserDetail::where('phone_number', $user_phone)->first();
            if (!$user) {
                return response()->json(['error' => 'User not found.'], 404);
            }

            $user_id = $user->id;

            $validated = $request->validate([
                'origin_station'      => 'required|string|max:255',
                'destination_station' => 'required|string|max:255',
                'journey_date'        => 'required|date',
                'was_delayed'         => 'required|boolean',
                'delay_minutes'       => 'nullable|integer|min:0'
            ]);
            $validated['user_id'] = $user_id;

            $journey = DelayDogJourney::create($validated);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Journey recorded successfully.',
                'data' => $journey
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong.',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function delaydogclaims(Request $request, $user_phone, $journey_uuid)
        {
            try{
                DB::beginTransaction();
            $user = DelayDogUserDetail::where('phone_number', $user_phone)->first();
            if (!$user) {
                return response()->json(['error' => 'User not found.'], 404);
            }
            $journey = DelayDogJourney::where('uuid', $journey_uuid)->first();
            if (!$journey) {
                return response()->json(['error' => 'Journey not found.'], 404);
            }
            $validated = $request->validate([
                'claim_reference'   => 'required|string|max:255',
                'status'            => 'required|string|max:255',
                'submitted_at'      => 'required|date',
                'response_data'     => 'required|string|max:255',
            ]);
            $validated = array_merge($validated, [
                'user_id' => $user->id,
                'journey_id' => $journey->id,
                'response_data' => json_encode($validated['response_data'])
            ]);
            
            $claim_details = DelayDogClaims::create($validated);
            DB:commit();
            
            return response()->json([
                'status' => true,
                'message' => 'Claim saved successfully.',
                'data' => $claim_details
            ]);
        }
        catch(e){
            DB::rollback();
        }
        }
    public function delayDogTickets(Request $request){
        try {
            
            DB::beginTransaction();
            $validated = $request->validate([
                'journey_id'     => 'required|exists:delay_dog_journeys,id',
                'ticket_image'   => 'required|image|mimes:jpeg,png,jpg,gif,svg',
            ]);
            $imagePath = null;
          
            if ($request->hasFile('ticket_image')) {
                $image = $request->file('ticket_image');
                if ($image->isValid()) {
                    $imageName = time() . '.' . $image->getClientOriginalExtension();
                    $imagePath = $image->storeAs('TicketImages', $imageName, 'public');
                }
            }
            $ticketDetails = delayDogTickets::create([
                'journey_id' => $validated['journey_id'],
                'ticket_image_path' => $imagePath,
            ]);
        
            DB::commit();
            return response()->json([
                'status' => true,
                'message' => 'Ticket uploaded successfully',
                'data' => $ticketDetails,
            ]);
        } 
        catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'status' => false,
                'message' => 'Ticket upload failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
