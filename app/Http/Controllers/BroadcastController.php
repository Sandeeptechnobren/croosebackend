<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
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
}

