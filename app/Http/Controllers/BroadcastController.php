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
}
