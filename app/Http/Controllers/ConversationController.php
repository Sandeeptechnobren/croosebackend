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
    // Read the stored conversation thread (populated by the Chatterly webhook),
    // instead of the legacy WHAPI gateway which does not work with Chatterly instances.
    $number = preg_replace('/[^0-9]/', '', $validated['phone_number']);

    $rows = Conversation::where('client_id', $client_id)
        ->where('space_id', $validated['space_id'])
        ->where('whatsapp_number', $number)
        ->orderBy('message_timestamp')
        ->orderBy('id')
        ->get();

    $formattedMessages = [];
    foreach ($rows as $row) {
        $time = (string) ($row->message_timestamp ?? $row->created_at);
        if (!empty($row->user_message)) {
            $formattedMessages[] = ['sender' => 'user', 'text' => $row->user_message, 'timestamp' => $time];
        }
        if (!empty($row->bot_response)) {
            $formattedMessages[] = ['sender' => 'bot', 'text' => $row->bot_response, 'timestamp' => $time];
        }
    }

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
