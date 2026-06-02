<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Helpers\TargetCustomers;
use Illuminate\Support\Facades\Auth;
use App\Models\Space_whapichannel_details;

class MessageService
{
    public static function send(string $phone, string $message, int $spaceId): bool
    {
    if (!$phone) {
        Log::error('WHAPI: phone missing');
        return false;
    }
    $whapi_token = Space_whapichannel_details::where('space_id', $spaceId)->value('token');

    if (!$whapi_token) {
        Log::error("WHAPI token not found for space {$spaceId}");
        return false;
    }
    $phone = preg_replace('/\D/', '', $phone);
    if (strlen($phone) === 10) {
        $phone = '91' . $phone;
    }
    try {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $whapi_token,
            'Content-Type'  => 'application/json',
        ])->post(env('WHAPI_URL'), [
            'to'   => $phone,
            'body' => $message,
        ]);
        if ($response->successful()) {
            Log::info("WHAPI message sent → {$phone}");
            return true;
        }
        Log::error('WHAPI failed', [
            'phone' => $phone,
            'resp'  => $response->body()
        ]);
        return false;
    } catch (\Exception $e) {
        Log::error("WHAPI exception → {$e->getMessage()}");
        return false;
    }
    }

// public function sendScheduledMessages($targetId, $message,$spaceId)
// {
//     $user = Auth::user();
//     $customers = TargetCustomers::getCustomersByTargetMessageId($targetId, $user->id,$spaceId);
//     dd($customers);
//     foreach ($customers as $clientCustomer) {
        
//         if (!$clientCustomer->customer || !$clientCustomer->customer->whatsapp_number) {
//             continue;
//         }
//         $phone = ltrim($clientCustomer->customer->whatsapp_number, '+');
//         Http::withHeaders([
//             'Authorization' => 'Bearer ' . config('services.whapi.token'),
//             'Content-Type'  => 'application/json',
//         ])->post('https://gate.whapi.cloud/messages/text', [
//             'to'   => $phone,
//             'body' => $message,
//         ]);
//     }
//     return response()->json([
//         'status'  => 'success',
//         'message' => 'Messages sent successfully',
//     ]);
// }

    public function sendScheduledMessages($targetId, $message,$spaceId)
    {
    $user = Auth::user();
    $whapi_token=Space_whapichannel_details::where('space_id',$spaceId)->value('token');
    
    $customers = TargetCustomers::getCustomersByTargetMessageId($targetId, $user->id,$spaceId);
    foreach ($customers as $phone) {

        if (!$phone) {
            continue;
        }

        $phone = ltrim($phone, '+');

        Http::withHeaders([
            'Authorization' => 'Bearer ' . $whapi_token,
            'Content-Type'  => 'application/json',
        ])->post('https://gate.whapi.cloud/messages/text', [
            'to'   => $phone,
            'body' => $message,
        ]);
    }
    return response()->json([
        'status'  => 'success',
        'message' => 'Messages sent successfully',
    ]);
    }
  
    protected function token(int $spaceId): ?string
    {
        return Space_whapichannel_details::where('space_id', $spaceId)->value('token');
    }
    public function getChatByPhone(int $spaceId, string $phone, int $count = 200): array
    {
        // Show ONLY messages received AFTER the WhatsApp connected (captured by the
        // Chatterly webhook into `conversations`). No pre-connection history, and each
        // chat is matched by its exact number so threads never mix.
        $number = preg_replace('/[^0-9]/', '', $phone);

        // No number (e.g. a group with no personal phone) -> no per-contact thread.
        if ($number === '') {
            return ['success' => true, 'chat_id' => $phone, 'data' => ['messages' => []]];
        }

        $rows = \App\Models\Conversation::where('space_id', $spaceId)
            ->where('whatsapp_number', $number)
            ->orderBy('message_timestamp')
            ->orderBy('id')
            ->limit($count)
            ->get();

        $messages = [];
        foreach ($rows as $r) {
            $ts = strtotime((string) ($r->message_timestamp ?? $r->created_at));
            if (!empty($r->user_message)) { $messages[] = ['from_me' => false, 'body' => $r->user_message, 'timestamp' => $ts]; }
            if (!empty($r->bot_response)) { $messages[] = ['from_me' => true, 'body' => $r->bot_response, 'timestamp' => $ts]; }
        }

        return ['success' => true, 'chat_id' => $number, 'data' => ['messages' => $messages]];
    }

    public function sendText(int $spaceId, string $to, string $body)
    {
    $token = $this->token($spaceId);
    if (!$token) {
        return [
            'success' => false,
            'message' => 'Invalid space_id or token not found'
        ];
    }
    $res = Http::withHeaders([
        'Authorization' => 'Bearer ' . $token,
        'Accept'        => 'application/json',
        'Content-Type'  => 'application/json',
    ])->post(
        'https://gate.whapi.cloud/messages/text',
        [
            'typing_time' => 0,
            'to'   => $to,
            'body' => $body
        ]
    );
    return $res->json();
    }
}
