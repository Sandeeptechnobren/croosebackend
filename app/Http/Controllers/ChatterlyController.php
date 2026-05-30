<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use App\Models\Space;
use App\Models\Space_whapichannel_details;

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

                $startResponse = Http::timeout(30)
                    ->post(
                        "{$this->baseUrl}/instance/start",
                        [
                            'token' => $this->token,
                            'instance_name' => $existing->name
                        ]
                    );

                return response()->json([
                    'message' => 'Instance already exists for this space.',
                    'instance_status' => 1,
                    'instance_id' => $existing->instance_id,
                    'instance_token' => $existing->token,
                    'payment_status' => $existing->payment_status,
                    'start_status' => $startResponse->status(),
                    'start_response' => $startResponse->body()
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

            $name = trim(
                $space->chatbot_name
                ?: $space->name
                ?: ''
            );

            if (empty($name)) {
                $name = 'Croose-Space-' . $space->id;
            }

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
            'space_id' => 'required|exists:Space_whapichannel_details,space_id'
        ]);

        $instance = Space_whapichannel_details::where(
            'space_id',
            $validated['space_id']
        )->first();

        if (!$instance) {
            return response()->json([
                'status' => false,
                'message' => 'Instance not found'
            ],404);
        }

        if ((int)$instance->_instance_activation_status === 1) {

            return response()->json([
                'status' => true,
                'connected' => true,
                'instance_activation_status' => 1,
                'message' => 'WhatsApp already connected'
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

        $instance_activation_status =
            Space_whapichannel_details::where(
                'space_id',
                $validated['space_id']
            )->value('_instance_activation_status');

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
}
