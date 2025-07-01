<?php
namespace App\Http\Controllers;

use App\Models\Appointment;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Exception;

use Illuminate\Support\Facades\Auth;

class AppointmentController extends Controller
{
    // ✅ Create Appointment    
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'client_id'    => 'required|exists:clients,id',
                'customer_id'  => 'required|exists:customers,id',
                'service_id'   => 'nullable|exists:services,id',
                'scheduled_at' => 'required|date',
                'notes'        => 'nullable|string',
            ]);

            $appointment = Appointment::create([
                'client_id'    => $validated['client_id'],
                'customer_id'  => $validated['customer_id'],
                'service_id'   => $validated['service_id'] ?? null,
                'scheduled_at' => $validated['scheduled_at'],
                'status'       => 'pending',
                'notes'        => $validated['notes'] ?? null,
            ]);

            $appointment->load('service');

            return response()->json([
                'success' => true,
                'data' => [
                    'id'            => $appointment->id,
                    'client_id'     => $appointment->client_id,
                    'customer_id'   => $appointment->customer_id,
                    'service_id'    => $appointment->service_id,
                    'service_name'  => $appointment->service?->name ?? null,
                    'scheduled_at'  => $appointment->scheduled_at,
                    'status'        => $appointment->status,
                    'notes'         => $appointment->notes,
                ]
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
        public function show()
            {
                $user=Auth::user();
                
                $appointment = Appointment::where('client_id',$user->id)->get();

                return response()->json([
                    'success' => true,
                    'data' => $appointment
                ]);
            }

}
