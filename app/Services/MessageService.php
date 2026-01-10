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
public static function send(string $phone, string $message): bool
    {
        if (empty($phone)) {
            Log::error('WHAPI: phone missing');
            return false;
        }
        $phone = preg_replace('/\D/', '', $phone);
        if (strlen($phone) === 10) {
            $phone = '91' . $phone;
        }
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . env('WHAPI_TOKEN'),
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

    public function getChatByPhone(int $spaceId, string $phone, int $count = 100): array
    {
        $token = $this->token($spaceId);
        if (!$token) {
            return ['success' => false, 'message' => 'WHAPI token not found'];
        }

        $chatId = $phone . '@s.whatsapp.net';

        $res = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept'        => 'application/json',
        ])->get("https://gate.whapi.cloud/messages/list/{$chatId}", ['count' => $count]);

        return $res->ok()
            ? ['success' => true, 'chat_id' => $chatId, 'data' => $res->json()]
            : ['success' => false, 'message' => 'WHAPI request failed', 'error' => $res->json()];
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

