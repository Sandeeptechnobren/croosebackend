<?php

namespace App\Http\Controllers;
use App\Models\Client;
use App\Models\BroadcastSchedule;
use App\Models\TargetMessage;
use Illuminate\Http\Request;
use App\Models\BroadcastHeader;


class BroadcastController extends Controller
{
    public function new()
    {
        $customers = Client::where('status', 'new')->get();

        return response()->json([
            'success' => true,
            'type'    => 'new',
            'count'   => $customers->count(),
            'data'    => $customers
        ]);
    }
    public function active()
    {
        $customers = Client::where('status', 'active')->get();

        return response()->json([
            'success' => true,
            'type'    => 'active',
            'count'   => $customers->count(),
            'data'    => $customers
        ]);
    }
   public function recent()
{
    $clientId = auth()->user()->client_id;

    $customers = Client::whereDate('created_at', '>=', now()->subDays(7))
        ->whereIn('id', function ($q) use ($clientId) {
            $q->select('customer_id')
              ->from('client_customer')
              ->where('client_id', $clientId);
        })
        ->get();

    return response()->json([
        'success' => true,
        'type'    => 'recent',
        'count'   => $customers->count(),
        'data'    => $customers
    ]);
}
           public function all(Request $request)
    {
    $query = Client::query();
    if ($request->has('client_id')) {
        $query->where('client_id', $request->client_id);
    }
    $customers = $query->get();
    return response()->json([
        'success' => true,
        'type'    => 'all',
        'count'   => $customers->count(),
        'data'    => $customers
    ]);
    }

       public function schedule(Request $request)
    {
    $request->validate([
        'broadcast_id'      => 'required|integer',
        'scheduled_at'      => 'required|date',
        'target_customers'  => 'required|array|min:1',
        'target_customers.*'=> 'integer'
    ]);
    $schedule = BroadcastSchedule::create([
        'broadcast_id' => $request->broadcast_id,
        'scheduled_at' => $request->scheduled_at
    ]);
    foreach ($request->target_customers as $customerId) {
        BroadcastTarget::create([
            'broadcast_id' => $request->broadcast_id,
            'customer_id'  => $customerId
        ]);
    }
    return response()->json([
        'success' => true,
        'message' => 'Broadcast scheduled successfully',
        'data'    => [
            'schedule' => $schedule,
            'targets'  => $request->target_customers
        ]
    ], 201);
    }
    public function Schedulelist()
    {
        $schedules = BroadcastSchedule::orderBy('scheduled_at', 'asc')->get();

        return response()->json([
            'success' => true,
            'count'   => $schedules->count(),
            'data'    => $schedules
        ]);
    }
}


