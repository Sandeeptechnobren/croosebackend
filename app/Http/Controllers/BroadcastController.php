<?php

namespace App\Http\Controllers;
use App\Models\Client;
use App\Models\BroadcastSchedule;
use App\Models\TargetMessage;
use Illuminate\Http\Request;
use App\Models\BroadcastHeader;
use App\Http\Requests\BroadcastStoreRequest;
use App\Http\Requests\BroadcastUpdateRequest;
use App\Http\Resources\BroadcastResource;
use App\Services\BroadcastService;


class BroadcastController extends Controller
{
     protected $service;
    public function __construct(BroadcastService $service)
    {
        $this->service = $service;
    }
    public function index()
    {
        return BroadcastResource::collection(
            $this->service->getAll()
        );
    }
    public function show($id)
    {
        return new BroadcastResource(
            $this->service->getById($id)
        );
    }
    public function store(BroadcastStoreRequest $request)
    {
        $broadcast = $this->service->create($request->validated());
        return new BroadcastResource($broadcast);
    }
    public function update(BroadcastUpdateRequest $request, $id)
    {
        $broadcast = $this->service->update($id, $request->validated());
        return new BroadcastResource($broadcast);
    }
    public function destroy($id)
    {
        $this->service->delete($id);
        return response()->json(['message' => 'Deleted successfully']);
    }
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
