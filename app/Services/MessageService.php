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
            Log::error('Broadcast send: phone missing');
            return false;
        }
        // Route through Chatterly (the old WHAPI gateway is dead).
        return (bool) (self::sendViaChatterly($spaceId, $phone, $message)['success'] ?? false);
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

    public function sendScheduledMessages($targetId, $message, $spaceId)
    {
        $user = Auth::user();
        $customers = TargetCustomers::getCustomersByTargetMessageId($targetId, $user->id, $spaceId);

        // Deliver to each target customer via Chatterly (the old WHAPI gateway is dead).
        $sent = 0; $failed = 0;
        foreach ($customers as $phone) {
            if (!$phone) { continue; }
            $res = self::sendViaChatterly((int) $spaceId, (string) $phone, $message);
            if ($res['success'] ?? false) { $sent++; } else { $failed++; }
        }

        return response()->json([
            'status'  => 'success',
            'message' => "Broadcast sent. Delivered: {$sent}, failed: {$failed}.",
            'sent'    => $sent,
            'failed'  => $failed,
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

    /**
     * Send a WhatsApp text via the space's Chatterly instance and persist it locally so it
     * shows in the thread and survives a refresh (the webhook de-dupes its echo of this send).
     *
     * @param string      $to     Recipient phone (digits) used for delivery.
     * @param string|null $chatId Open chat's id (e.g. "1234@lid"); the thread is keyed by its
     *                            digits, so we store under it to keep the message in the right chat.
     */
    public function sendText(int $spaceId, string $to, string $body, ?string $chatId = null)
    {
        return self::sendViaChatterly($spaceId, $to, $body, $chatId);
    }

    /**
     * Core WhatsApp text sender via the space's Chatterly instance. Persists the outgoing
     * message locally so it shows in the thread and survives a refresh (the webhook de-dupes
     * its echo). Shared by direct chat replies, "Send Message", and scheduled broadcasts.
     */
    public static function sendViaChatterly(int $spaceId, string $to, string $body, ?string $chatId = null): array
    {
        $instance = Space_whapichannel_details::where('space_id', $spaceId)->first();
        if (!$instance || empty($instance->token) || empty($instance->name)) {
            return ['success' => false, 'message' => 'WhatsApp instance not found for this space'];
        }

        // Number to deliver to (digits). A bare 10-digit number defaults to India (+91),
        // matching the previous behaviour.
        $sendNumber = preg_replace('/[^0-9]/', '', $to);
        if ($sendNumber === '' && $chatId) {
            $sendNumber = preg_replace('/[^0-9]/', '', explode('@', $chatId)[0]);
        }
        if ($sendNumber === '') {
            return ['success' => false, 'message' => 'Invalid recipient'];
        }
        if (strlen($sendNumber) === 10) {
            $sendNumber = '91' . $sendNumber;
        }

        // Key the stored copy by the OPEN chat's id digits (how getChatByPhone looks it up),
        // so an outgoing message lands in the same thread the user is viewing.
        $storeKey = $chatId ? preg_replace('/[^0-9]/', '', explode('@', $chatId)[0]) : $sendNumber;
        if ($storeKey === '') { $storeKey = $sendNumber; }

        try {
            $res = Http::timeout(20)->post(
                "https://chatterly.easycoders.in/api/instance/send/{$instance->name}",
                ['token' => $instance->token, 'number' => $sendNumber, 'message' => $body]
            );
            $json = $res->json();

            if ($res->successful() && ($json['success'] ?? false)) {
                DB::table('conversations')->insert([
                    'client_id'         => $instance->client_id,
                    'space_id'          => $spaceId,
                    'whatsapp_number'   => $storeKey,
                    'user_message'      => '',
                    'bot_response'      => $body,   // outgoing => from_me
                    'session_id'        => $storeKey,
                    'message_timestamp' => now(),
                    'created_at'        => now(),
                    'updated_at'        => now(),
                ]);

                return ['success' => true, 'message_id' => $json['messageId'] ?? null];
            }

            Log::error('Chatterly send failed', ['number' => $sendNumber, 'resp' => $res->body()]);
            return ['success' => false, 'message' => 'Send failed'];
        } catch (\Throwable $e) {
            Log::error('Chatterly send exception', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Send error'];
        }
    }
}
