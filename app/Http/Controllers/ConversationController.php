<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Models\Conversation;
use App\Models\Space;
use App\Models\Space_whapichannel_details;
use App\Models\Space_whapipayment_details;

class ConversationController extends Controller
{
public function get_conversations(Request $request){
    $client_id = Auth::user()->id;
    $validated = $request->validate([
        'phone_number' => 'required',
        'space_id'     => 'required',
    ]);
    $phone_number = $validated['phone_number'];
    $space = Space::where('id',$validated['space_id'])->first();
    $whapi_token = Space_whapichannel_details::where('space_id',$space->id)->value('token');        
    $whapi_chat_id = $phone_number.'@s.whatsapp.net';
    $client = new \GuzzleHttp\Client();
    $response = $client->request('GET',"https://gate.whapi.cloud/messages/list/{$whapi_chat_id}", [
        'headers' => [
            'accept' => 'application/json',
            'authorization' => "Bearer {$whapi_token}",
        ],
    ]);
    $messages = json_decode($response->getBody(), true);
    $formattedMessages = collect($messages['messages'] ?? [])
        ->map(function($msg) {
            $sender = ($msg['from_me'] ?? false) ? 'bot' : 'user';
            return [
                'sender'    => $sender,
                'text'      => $msg['text']['body'] ?? '',
                'timestamp' => isset($msg['timestamp']) 
                                ? date('Y-m-d H:i:s', $msg['timestamp']) 
                                : null,
                'status'    => $msg['ack'] ?? null,
                'raw_time'  => $msg['timestamp'] ?? 0, // keep raw timestamp for sorting
            ];
        })
        ->sortBy('raw_time')  // sort ascending (oldest → newest)
        ->values()            // reset array keys
        ->map(function($msg) {
            unset($msg['raw_time']); // remove helper key before returning
            return $msg;
        });

    return response()->json([
        "status"        => 200,
        "message"       => "true",
        "conversations" => $formattedMessages,
    ]);
}

    public function total_chats(Request $response){
        $client_id=Auth::user()->id;
        $total_chats=Conversation::where('client_id',$client_id)
            ->distinct('whatsapp_number')->count();
        return response()->json(['status'=>true,'message'=>'Total chats','total_chats'=>$total_chats]);
    }


}
