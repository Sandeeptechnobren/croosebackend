<?php

namespace App\Services\Ordiio;

use Illuminate\Http\Request;
use App\Models\OrdiioUser;
use App\Models\WhitelistChannel;

class OrdiioAdminService
{
public function registeredUsers(Request $request, $data)
    {
        $registeredUsers = OrdiioUser::where('status', 1)
            ->get([
                'id',
                'first_name',
                'last_name',
                'email',
                'type',
                'is_subscriber',
                'company_type',
                'created_at',
                'updated_at',
                'status'
            ]);
        
        return $registeredUsers;
    }
public function userStatistics(Request $request, $data)
    {
        $activeUsers = OrdiioUser::where('status', 1)->count();
        $subscribedUsers = OrdiioUser::where('is_subscriber', 1)->count();
        $whiteListedchannels = WhitelistChannel::whereNull('deleted_at')->count();

        return [
            'activeUsers' => $activeUsers,
            'subscribedUsers' => $subscribedUsers,
            'whiteListedchannels' => $whiteListedchannels
        ];
    }


}
