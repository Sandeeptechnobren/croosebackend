<?php

namespace App\Http\Controllers;
use App\Models\Customer;
use App\Models\BroadcastSchedule;
use App\Models\TargetMessage;
use Illuminate\Http\Request;
use App\Models\BroadcastHeader;
use App\Http\Requests\BroadcastStoreRequest;
use App\Http\Requests\BroadcastUpdateRequest;
use App\Http\Resources\BroadcastResource;
use App\Services\BroadcastService;
use App\Services\MessageService;


class BroadcastController extends Controller
{
    protected $service;
    protected $messageservice;
    public function __construct(BroadcastService $service, MessageService $messageservice)
    {
        $this->service = $service;
        $this->messageservice = $messageservice;
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
     public function customers($id)
    {
        $targetMessage = TargetMessage::findOrFail($id);

        $customers = $targetMessage->customers();

        return response()->json([
            'success' => true,
            'target_type' => $targetMessage->target_type,
            'count' => $customers->count(),
            'data' => $customers,
        ]);
    }
    public function targetlist()
    {
        $targets = TargetMessage::all()->map(function ($target) {
            $customers = $target->customers();
            return [
                'id' => $target->id,
                'name' => $target->name,
                'target_type' => $target->target_type,
                'description' => $target->description,
                //'customer_count' => $customers->count(),
            ];
        });
        return response()->json([
            'success' => true,
            'count' => $targets->count(),
            'data' => $targets,
        ]);
    }
    public function sendMessage(Request $request){
        $validate=$request->validate([
           'targetId'=>'required|integer|exists:target_messages,id',
           'spaceId'=>'required|integer|exists:spaces,id',
           'message'=>'required|string',
        ]);
        $this->messageservice->sendScheduledMessages($targetId=$validate['targetId'],$message=$validate['message'],$spaceId=$validate['spaceId']);   
        return response()->json(['message'=>'Messages are being sent.']);
    }

    
       public function getChat(Request $request, $phone)
   {
    $spaceId = (int) $request->get('space_id');
    if (!$spaceId) {
        return response()->json([
            'success' => false,
            'message' => 'space_id is required'
        ], 400);
    }
    $result = $this->messageservice->getChatByPhone(
        $spaceId,
        $phone
    );
    return response()->json($result, $result['success'] ? 200 : 400);
   }
    
       public function sendtext(Request $request, Messageservice $whapi)
   {
    $request->validate([
        'space_id' => 'required|integer',
        'phone'    => 'required',
        'message'  => 'required|string'
    ]);
    return $whapi->sendText(
        $request->space_id,
        $request->phone,
        $request->message
    );
   }
}

