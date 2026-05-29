<?php
namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Space;
use App\Models\Service;
use Exception;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Services\GoogleCalendarService;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class AppointmentController extends Controller
{  
    
public function show()
{  
    $user = Auth::user();
    $appointments = Appointment::where('client_id', $user->id)->paginate(10);
    $formattedAppointments = $appointments->through(function ($appointment) {
        $customer = Customer::find($appointment->customer_id);
        $service_name = DB::table('services')
            ->where('id', $appointment->service_id)
            ->value('name');
        $space_name = Space::where('id', $appointment->space_id)->value('name');
        return [
            'id' => $appointment->id,
            'space_name' => $space_name,
            'customer_name' => $customer->name ?? null,
            'customer_email'=>$customer->email??null,
            'customer_address'=>$customer->address??null,
            'customer_number' => $customer->whatsapp_number ?? null,
            'service_name' => $service_name,
            'date_created'=>Carbon::parse($appointment->created_at)->format('d-M-Y'),
            'date' => Carbon::parse($appointment->appointment_date)->format('d-M-Y'). ' ' . $appointment->start_time,
            'status' => $appointment->status,
        ];
    });
    return response()->json([
        'success' => true,
        'data' => $formattedAppointments->items(),
        'meta' => [
            'current_page' => $appointments->currentPage(),
            'last_page'    => $appointments->lastPage(),
            'per_page'     => $appointments->perPage(),
            'total'        => $appointments->total(),
        ]
    ]);
}


public function getAppointments($space_phone, $customer_phone)
    {
    try {
        $space = DB::table('spaces')->where('space_phone', $space_phone)->first();
        $space_id=$space->id;
        $customer = DB::table('customers')->where('whatsapp_number', $customer_phone)->first();
        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => 'You are not the registered customer',
            ], 404);
        }
        $appointments = Appointment::with('service')
            ->where('space_id', $space_id)
            ->where('customer_id', $customer->id)
            ->get();
        return response()->json([
            'success' => true,
            'data' => $appointments->map(function ($appointment) {
                return [
                    'id'            => $appointment->id,
                    'client_id'     => $appointment->client_id,
                    'space_id'      => $appointment->space_id,
                    'customer_id'   => $appointment->customer_id,
                    'service_id'    => $appointment->service_id,
                    'service_name'  => $appointment->service?->name,
                    'scheduled_at'  => $appointment->scheduled_at,
                    'status'        => $appointment->status,
                    'notes'         => $appointment->notes,
                ];
            }),
            'message' => 'Appointment List.'
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Something went wrong.',
            'error'   => $e->getMessage()
        ], 500);
    }
    }
public function updateStatus(Request $request)
    {
    try {
        $client = auth()->user(); 
        $validated = $request->validate([
            'status' => 'required|in:pending,completed,cancelled,confirmed',
            'id'     => 'required|integer'
        ]);
        $appointment = \App\Models\Appointment::where('id', $validated['id'])
                        ->where('client_id', $client->id)
                        ->first();
        if (!$appointment) {
            return response()->json([
                'success' => false,
                'message' => 'Appointment not found or does not belong to you.'
            ], 404);
        }
        $appointment->status = $validated['status'];
        $appointment->save();
        return response()->json([
            'success' => true,
            'message' => 'Status updated successfully.',
            'data' => [
                'id'     => $appointment->id,
                'status' => $appointment->status,
            ]
        ], 200);
    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json([
            'success' => false,
            'errors' => $e->errors()
        ], 422);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Something went wrong.',
            'error' => $e->getMessage()
        ], 500);
    }
    }

public function storeappointment(Request $request,$space_phone,$customer_phone)
    {
    DB::beginTransaction();
    try {
            $space = DB::table('spaces')->where('space_phone', $space_phone)->first();
            if (!$space) {  
                return response()->json(['success' => false, 'message' => 'Space not found.',], 200);
            }
            $space_id=$space->id;
            $client_id = $space->client_id;
            $customer = DB::table('customers')->where('whatsapp_number', $customer_phone)->first();
            $customer_id=$customer->id;
            if(!$customer)
                {
                return response()->json([ 'message'=>'You must login First!'],404);
                }
            $validator = Validator::make($request->all(),[
                    'service_id'        => 'required|exists:services,id',
                    'appointment_date'  => 'required|date',
                    'start_time'        => 'required|date_format:H:i',
                    'end_time'          => 'required|date_format:H:i',
                    'images'            => 'nullable|image|mimes:jpeg,png,jpg,gif,svg',
                    'notes'             => 'nullable|string',
            ]);
            $service = Service::where('id', $request->service_id)->where('space_id', $space_id)->first();
            if(!$service){
                return response()->json(['success' => false,'message' => 'The selected service does not belong to the specified space.'], 400);
                }
            $imagePath=null;
            if($validator->fails()){
                DB::rollBack();
                $errors=$validator->errors()->all();
                $errorsString=implode(',',$errors);
                return response()->json(['success'=>false, 'message'=>$errorsString, 'errors'=>$validator->errors()],422);
            }
            $validated=$validator->validated();   
            if($request->hasFile('images')){
                $image=$request->file('images');
                if($image->isValid()){
                    $imageName=time().'.'.$image->getClientOriginalExtension();
                    $imagePath=$image->storeAs('Appointments',$imageName,'public');
                }
            }
            $amount=$service->price;
            $appointment = Appointment::create([
                    'client_id'    => $client_id,
                    'space_id'     =>$space_id,
                    'customer_id'  => $customer_id,
                    'service_id'   => $validated['service_id'] ?? null,
                    'appointment_date' => $validated['appointment_date'],
                    'start_time'=> $validated['start_time'],
                    'end_time'=> $validated['end_time'],
                    'amount'       => $amount,
                    'images'       => $imagePath,
                    'status'       => 'pending',
                    'notes'        => $validated['notes'] ?? null,
                ]);
            $paymentUrl = "http://68.183.108.227/croose/public/index.php/api/pay-now1/{$appointment->uuid}";
            DB::commit();
            $appointment->load('service');
            return response()->json(['success' => true, 'data' => $appointment, 'payment_url'  => $paymentUrl], 200);
        } 
        catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Something went wrong.', 'error'   => $e->getMessage()], 500);
        }
    }

public function getAvailableSlots(Request $request,$space_phone)
    {
        $request->validate([
            'service_id' => 'required|exists:services,id',
            'date'       => 'required|date',
        ]);
        $space = Space::where('space_phone', $space_phone)->first();
        if (!$space) {
            return response()->json(['message' => 'Space not found'], 404);
        }
        $space_id = $space->id;
        $service = Service::find($request->service_id);
        if (!$service) {
            return response()->json([
                'success' => false,
                'message' => 'Service not found.'
            ], 404);
        }
        $duration = $service->duration_minutes;
        $date = Carbon::parse($request->date);
        $workingHours = DB::table('spaces')
            ->where('id', $space_id)
            ->select('start_time', 'end_time')
            ->first();
        if (!$workingHours) {
            return response()->json(['slots' => [], 'message' => 'No working hours defined.']);
        }
        $startOfDay = Carbon::parse($date->toDateString() . ' ' . $workingHours->start_time);
        $endOfDay   = Carbon::parse($date->toDateString() . ' ' . $workingHours->end_time);
        $bookedSlots = DB::table('appointments')
            ->where('space_id', $space_id)
            ->where('appointment_date', $date->toDateString())
            ->get(['start_time', 'end_time']);
        $booked = [];
        foreach ($bookedSlots as $slot) {
            $booked[] = [
                'start' => Carbon::parse($date->toDateString() . ' ' . $slot->start_time),
                'end'   => Carbon::parse($date->toDateString() . ' ' . $slot->end_time),
            ];
        }
        usort($booked, fn($a, $b) => $a['start']->lt($b['start']) ? -1 : 1);
        $booked[] = ['start' => $endOfDay, 'end' => $endOfDay];
        $availableSlots = [];
        $current = $startOfDay;
        foreach ($booked as $slot) {
            while ($current->copy()->addMinutes($duration)->lte($slot['start'])) {
                $slotEnd = $current->copy()->addMinutes($duration);
                $availableSlots[] = [
                    'start' => $current->format('H:i'),
                    'end'   => $slotEnd->format('H:i'),
                ];
                $current->addMinutes($duration);
            }
            if ($current->lt($slot['end'])) {$current = $slot['end']->copy();}
        }
        return response()->json(['status'  => true,'slots'   => $availableSlots,'message' => 'Available time slots fetched successfully.']);
    }
public function appointment_statistics()
{
    try {
        $client_id = Auth::user()->id;
        $today = Carbon::today();
        $yesterday = Carbon::yesterday();
        $total_appointments = Appointment::where('client_id', $client_id)->count();
        $total_appointments_today = Appointment::where('client_id', $client_id)->whereDate('created_at', $today)->count();
        $total_appointments_yesterday = Appointment::where('client_id', $client_id)->whereDate('created_at', $yesterday)->count();
        $total_appointments_growth = $this->calculateGrowth($total_appointments_yesterday, $total_appointments_today);
        $total_new_appointments = Appointment::where('client_id', $client_id)
            ->where('status', 'confirmed')
            ->whereDate('created_at', $today)
            ->count();
        $new_appointments_yesterday = Appointment::where('client_id', $client_id)
            ->where('status', 'confirmed')
            ->whereDate('created_at', $yesterday)
            ->count();
        $total_new_appointments_growth = $this->calculateGrowth($new_appointments_yesterday, $total_new_appointments);
        $cancelled_appointments = Appointment::where('client_id', $client_id)
            ->where('status', 'cancelled')
            ->count();
        $cancelled_appointments_today = Appointment::where('client_id', $client_id)
            ->where('status', 'cancelled')
            ->whereDate('created_at', $today)
            ->count();
        $cancelled_appointments_yesterday = Appointment::where('client_id', $client_id)
            ->where('status', 'cancelled')
            ->whereDate('created_at', $yesterday)
            ->count();
        $cancelled_appointments_growth = $this->calculateGrowth($cancelled_appointments_yesterday, $cancelled_appointments_today);
        return response()->json([
            'status' => true,
            'cancelled_appointments' => $cancelled_appointments,
            'total_appointments' => $total_appointments,
            'total_new_appointments' => $total_new_appointments,
            'total_appointments_growth' => $total_appointments_growth,
            'total_new_appointments_growth' => $total_new_appointments_growth,
            'cancelled_appointments_growth' => $cancelled_appointments_growth,
            'message' => 'Cancelled appointments.'
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Something went wrong.',
            'error' => $e->getMessage()
        ], 500);
    }
}
private function calculateGrowth($previous, $current)
{
    if ($previous == 0) {
        return $current > 0 ? 100 : 0;
    }
    return round((($current - $previous) / $previous) * 100, 2);
}


}
