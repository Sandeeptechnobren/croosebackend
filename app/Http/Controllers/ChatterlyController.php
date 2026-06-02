<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use App\Models\Space;
use App\Models\Space_whapichannel_details;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ChatterlyController extends Controller
{
    private $token;
    private $baseUrl;

    public function __construct()
    {
        $this->token = '45b189de454fe4c870ff11f22d874947617545e9ca9001fbd2db43091a10';
        $this->baseUrl = 'https://chatterly.easycoders.in/api';
    }

    public function createInstance(Request $request)
    {
        try {

            $validated = $request->validate([
                'space_id' => 'required|exists:spaces,id'
            ]);

            $space_id = $validated['space_id'];

            $existing = Space_whapichannel_details::where(
                'space_id',
                $space_id
            )->first();

            if ($existing && $existing->instance_id) {

                // Instance already exists — return immediately so the QR request isn't
                // blocked behind a slow instance/start call (the single-threaded dev server
                // serializes requests). The QR (qrpng) works without re-starting.
                return response()->json([
                    'message' => 'Instance already exists for this space.',
                    'instance_status' => 1,
                    'instance_id' => $existing->instance_id,
                    'instance_token' => $existing->token,
                    'payment_status' => $existing->payment_status,
                ]);
            }

            $space = Space::find($space_id);

            if (!$space) {
                return response()->json([
                    'status' => false,
                    'message' => 'Space not found'
                ], 404);
            }

            $currency = strtoupper($space->currency ?? 'USD');
            $activation_charge = 0;

            if ($currency === 'USD') {

                $activation_charge = 199;

            } elseif ($currency === 'GHS') {

                $activation_charge = 2000;

            } else {

                $apiKey = 'd20fa341e260799a2339a543';

                $currencyResponse = Http::get(
                    "https://v6.exchangerate-api.com/v6/{$apiKey}/latest/USD"
                );

                if ($currencyResponse->successful()) {

                    $rateData = $currencyResponse->json();

                    $exchangeRate =
                        $rateData['conversion_rates'][$currency]
                        ?? null;

                    if (!$exchangeRate) {
                        return response()->json([
                            'error' => 'Currency conversion unavailable'
                        ], 500);
                    }

                    $activation_charge =
                        round(199 * $exchangeRate, 2);

                } else {

                    return response()->json([
                        'error' => 'Currency API call failed'
                    ], 500);
                }
            }

            // Chatterly instance names are a GLOBAL namespace, so they must be unique
            // per space — otherwise two spaces with the same chatbot_name (e.g. "Agent")
            // collide on the same instance and cause connect/disconnect instability.
            $base = trim($space->chatbot_name ?: $space->name ?: 'Space');
            $name = $base . '-' . $space->id;

            $response = Http::timeout(30)
                ->post(
                    "{$this->baseUrl}/instance/create",
                    [
                        'token' => $this->token,
                        'instance_name' => $name
                    ]
                );

            if (!$response->successful()) {

                return response()->json([
                    'status' => false,
                    'message' => 'Create instance failed',
                    'status_code' => $response->status(),
                    'response' => $response->body()
                ], 500);
            }

            $data = $response->json();

            $instanceId =
                $data['instance']['id']
                ?? null;

            $instanceToken =
                $data['instance']['token']
                ?? '';

            if (!$instanceId) {

                return response()->json([
                    'status' => false,
                    'message' => 'Instance ID missing',
                    'data' => $data
                ], 500);
            }

            $startResponse = Http::timeout(30)
                ->post(
                    "{$this->baseUrl}/instance/start",
                    [
                        'token' => $this->token,
                        'instance_name' => $name
                    ]
                );

            // Tell Chatterly where to POST incoming WhatsApp messages for this instance
            $webhookUrl = env('CHATTERLY_WEBHOOK_URL', rtrim(config('app.url'), '/') . '/api/chatterly/webhook');
            $webhookResponse = Http::timeout(30)->post(
                "{$this->baseUrl}/instance/webhook/{$name}",
                [
                    'token' => $this->token,
                    'webhookUrl' => $webhookUrl,
                ]
            );

            Space_whapichannel_details::create([
                'space_id'          => $space_id,
                'client_id'         => $space->client_id,
                'payment_status'    => 'unpaid',
                'payment_method'    => 'chatterly',
                'payment_origin'    => 'dashboard',
                'payment_reference' => null,
                'payment_amount'    => $activation_charge,
                'instance_id'       => $instanceId,
                'token'             => $instanceToken,
                'status'            => 'active',
                'name'              => $name,
                'activation_charge' => $activation_charge
            ]);

            return response()->json([
                'message' => 'Instance created successfully',
                'instance_id' => $instanceId,
                'instance_token' => $instanceToken,
                'activation_charge' => $activation_charge,
                'currency' => $currency,
                'start_status' => $startResponse->status(),
                'start_response' => $startResponse->body(),
                'data' => $data
            ]);

        } catch (\Exception $e) {

            return response()->json([
                'status' => false,
                'message' => 'Server Error',
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ], 500);
        }
    }

  public function fetchQr(Request $request)
{
    try {

        $validated = $request->validate([
            'space_id' => 'required'
        ]);

        $instance = Space_whapichannel_details::where(
            'space_id',
            $validated['space_id']
        )->first();

        if (!$instance) {
            // The instance is still being created in the background — tell the
            // frontend to keep showing the loading spinner (not an error).
            return response()->json([
                'status' => false,
                'waiting' => true,
                'message' => 'Instance is being created'
            ], 200);
        }

        // Fast path: if the DB already knows it's connected, skip the QR entirely.
        // (The connection state is kept in sync separately by the 2s
        //  /instance_activation_status poll, so we DON'T make the slow status call here —
        //  that keeps the QR response fast: one Chatterly call instead of two.)
        if ((int)$instance->_instance_activation_status === 1) {
            return response()->json([
                'status' => true,
                'connected' => true,
                'instance_activation_status' => 1,
                'message' => 'WhatsApp connected'
            ]);
        }

        return $this->getQr($instance->name);

    } catch (\Exception $e) {

        return response()->json([
            'status' => false,
            'message' => 'QR fetch failed',
            'error' => $e->getMessage()
        ],500);
    }
}


private function getQr($instanceName)
{
    try {

        $url =
            "{$this->baseUrl}/instance/qrpng/{$instanceName}?token={$this->token}";

        $response = Http::timeout(20)->get($url);

        if ($response->successful()) {

            return response(
                $response->body(),
                200
            )->header(
                'Content-Type',
                'image/png'
            );
        }

        $body = $response->body();

        if (
            str_contains($body,'QR not generated yet')
        ) {

            return response()->json([
                'status'=>false,
                'waiting'=>true,
                'message'=>'QR generating'
            ]);
        }

        return response()->json([
            'status'=>false,
            'message'=>'Unable to fetch QR'
        ]);

    } catch (\Exception $e) {

        return response()->json([
            'status'=>false,
            'message'=>'QR exception',
            'error'=>$e->getMessage()
        ],500);
    }
}

public function instance_activation_status(Request $request)
{
    try {

        $validated = $request->validate([
            'space_id' => 'required|exists:Space_whapichannel_details,space_id',
        ]);

        $instance = Space_whapichannel_details::where(
            'space_id',
            $validated['space_id']
        )->first();

        // Live-check Chatterly and sync the flag BOTH ways:
        //   ready:true  -> 1 (connected)
        //   ready:false -> 0 (logged out from phone -> QR should show again)
        if ($instance) {
            // Cache the Chatterly status for a few seconds so the frequent activation poll
            // doesn't hammer Chatterly / block the single-threaded dev server (each live
            // status call takes ~1s and would otherwise stall the QR/chat requests).
            $iname = $instance->name;
            $status = cache()->remember("chatterly_status_{$iname}", 4, function () use ($iname) {
                return $this->checkConnectionStatus($iname);
            });
            if ($status !== null) {
                $newFlag = (($status['ready'] ?? false) === true) ? 1 : 0;
                if ((int)$instance->_instance_activation_status !== $newFlag) {
                    $instance->_instance_activation_status = $newFlag;
                    $instance->save();
                }
            }
        }

        $instance_activation_status = $instance ? $instance->_instance_activation_status : 0;

        return response()->json([
            'status' => true,
            'instance_activation_status' => (int)$instance_activation_status,
            'message' => 'instance activation status fetched successfully'
        ]);

    } catch (\Exception $e) {

        return response()->json([
            'status' => false,
            'message' => 'Server Error',
            'error' => $e->getMessage()
        ], 500);
    }
}
public function markRead(Request $request)
{
    try {
        $validated = $request->validate([
            'space_id' => 'required',
            'chat_id'  => 'required',
        ]);

        $instance = Space_whapichannel_details::where('space_id', $validated['space_id'])->first();
        if (!$instance || empty($instance->token) || empty($instance->name)) {
            return response()->json(['status' => false, 'message' => 'Instance not found'], 200);
        }

        // Tell Chatterly to mark the chat as read so its unread count clears.
        Http::timeout(15)->post(
            "{$this->baseUrl}/instance/mark-read/{$instance->name}",
            ['token' => $instance->token, 'chatId' => $validated['chat_id']]
        );

        return response()->json(['status' => true, 'message' => 'Marked read']);
    } catch (\Exception $e) {
        return response()->json(['status' => false, 'message' => 'mark-read failed', 'error' => $e->getMessage()], 200);
    }
}

private function checkConnectionStatus($instanceName)
{
    try {

        $response = Http::timeout(20)
            ->post(
                "{$this->baseUrl}/instance/status/{$instanceName}",
                [
                    'token' => $this->token
                ]
            );

        if (!$response->successful()) {
            return null;
        }

        return $response->json();

    } catch (\Exception $e) {
        return null;
    }
}

    /**
     * Public webhook hit by Chatterly when the linked WhatsApp sends/receives a message.
     * Stores the message in the conversations table so it appears in Live Chats.
     *
     * NOTE: payload field names are a best-guess and may need adjusting once a real
     * Chatterly message payload is observed (the raw body is logged for inspection).
     */
    public function webhook(Request $request)
    {
        try {
            $payload = $request->all();
            Log::info('Chatterly webhook received', ['payload' => $payload]);

            // 1) Identify the instance -> space/client
            $instanceName = $request->input('instance')
                ?? $request->input('instanceId')
                ?? $request->input('instance_name')
                ?? $request->input('session')
                ?? data_get($payload, 'data.instance')
                ?? data_get($payload, 'data.session');

            if (!$instanceName) {
                return response()->json(['status' => false, 'message' => 'No instance in payload'], 200);
            }

            // If multiple spaces share this instance name (legacy collision), prefer the
            // one that is currently connected, then the most recently created.
            $instance = Space_whapichannel_details::where('name', $instanceName)
                ->orderByDesc('_instance_activation_status')
                ->orderByDesc('id')
                ->first();
            if (!$instance) {
                return response()->json(['status' => false, 'message' => 'Unknown instance'], 200);
            }

            // 2) Extract the message (defensive against payload shape)
            $msg = $payload['data'] ?? $payload['message'] ?? $payload['payload'] ?? $payload;

            $body = data_get($msg, 'body')
                ?? data_get($msg, 'text')
                ?? data_get($msg, 'message')
                ?? data_get($msg, 'text.body')
                ?? '';
            $fromMe = (bool) (data_get($msg, 'fromMe') ?? data_get($msg, 'from_me') ?? false);
            $ts = data_get($msg, 'timestamp') ?? data_get($msg, 'time');

            $from = data_get($msg, 'from') ?? data_get($msg, 'chatId') ?? data_get($msg, 'sender') ?? data_get($msg, 'author');
            $to   = data_get($msg, 'to') ?? data_get($msg, 'recipient');

            // The chat is keyed by the CONTACT's number: for an outgoing message that's the
            // recipient (`to`); for an incoming message that's the sender (`from`).
            $contact = $fromMe ? ($to ?: $from) : ($from ?: $to);

            if (!$contact || $body === '') {
                return response()->json(['status' => true, 'message' => 'Ignored (no text)'], 200);
            }

            // normalise: strip @c.us / @lid / @s.whatsapp.net and non-digits
            $number = preg_replace('/[^0-9]/', '', explode('@', (string) $contact)[0]);

            // 3) Store as a conversation row
            DB::table('conversations')->insert([
                'client_id'         => $instance->client_id,
                'space_id'          => $instance->space_id,
                'whatsapp_number'   => $number,
                'user_message'      => $fromMe ? '' : $body,
                'bot_response'      => $fromMe ? $body : '',
                'session_id'        => $number,
                'message_timestamp' => $ts ? date('Y-m-d H:i:s', is_numeric($ts) ? (int) $ts : strtotime($ts)) : now(),
                'created_at'        => now(),
                'updated_at'        => now(),
            ]);

            return response()->json(['status' => true, 'message' => 'Stored'], 200);
        } catch (\Exception $e) {
            Log::error('Chatterly webhook error', ['error' => $e->getMessage()]);
            return response()->json(['status' => false, 'message' => 'Webhook error', 'error' => $e->getMessage()], 200);
        }
    }
}
