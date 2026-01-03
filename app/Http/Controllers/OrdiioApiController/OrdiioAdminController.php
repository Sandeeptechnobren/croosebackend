<?php

namespace App\Http\Controllers\OrdiioApiController;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\Ordiio\OrdiioAdminService;
use Illuminate\Support\Facades\Auth;

class OrdiioAdminController extends Controller
{
    protected $trackService;

public function __construct(OrdiioAdminService $trackService)
    {
        if (!auth()->check() || auth()->user()->type !== 'admin') {
            abort(403, 'Unauthorized');
        }

        $this->trackService = $trackService;
    }
public function registeredUsers(Request $request)
    {
        if (auth()->user()->type !== 'admin') {
            abort(403, 'Unauthorized');
        }
        $data = $request->all();
        $registeredUsers = $this->trackService->registeredUsers($request, $data);
        return response()->json([
            'status' => 200,
            'message' => 'Registered User list fetched successfully',
            'data' => $registeredUsers,
        ]);
    }
public function userStatistics(Request $request)
    {
        $data = $request->all();
        $stats = $this->trackService->userStatistics($request, $data);
        return response()->json([
            'status' => 200,
            'message' => 'User statistics fetched successfully',
            'activeUsers' => $stats['activeUsers'],
            'subscribedUsers' => $stats['subscribedUsers'],
            'whiteListedchannels'=>$stats['whiteListedchannels'],
        ]);
    }

}
