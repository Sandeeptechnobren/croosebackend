<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\Space;
use App\Models\Space_whapichannel_details;
use Illuminate\Support\Facades\Auth;
use App\Models\Space_whapipayment_details;


class WhapiController extends Controller
{

public function createInstance(Request $request)
{
    $client_id = Auth::user()->id;
    $validated = $request->validate([
        'space_id' => 'required|exists:spaces,id'
    ]);
    $space_id = $validated['space_id'];
    $existing = Space_whapichannel_details::where('space_id', $space_id)->first();
    if ($existing) {
        return response()->json([
            'message' => 'Instance already exists for this space.',
            'instance_status' => 1,
            'payment_status' => $existing->payment_status,
            'instance_activation_status' => $existing->_instance_activation_status
        ], 200);
    }
    $space = Space::find($space_id);
    $currency = strtoupper($space->currency);
    $activation_charge = 0;
    if ($currency === 'USD') {
        $activation_charge = 199;
    } elseif ($currency === 'GHS') {
        $activation_charge = 2000;
    } else {
        $apiKey = 'd20fa341e260799a2339a543';
        $response = Http::get("https://v6.exchangerate-api.com/v6/{$apiKey}/latest/USD");
        if ($response->successful()) {
            $rateData = $response->json();
            $exchangeRate = $rateData['conversion_rates'][$currency] ?? null;
            if (!$exchangeRate) {
                return response()->json(['error' => 'Currency conversion unavailable'], 500);
            }
            $activation_charge = round(199 * $exchangeRate, 2);
        } else {
            return response()->json(['error' => 'Currency API call failed'], 500);
        }
    }
    $name = $space->chatbot_name;
    $response = Http::withToken(env('WHAPI_MASTER_TOKEN'))
        ->put('https://manager.whapi.cloud/channels', [
            'name' => $name,
            'projectId' => 'TSu2uo9AC9nu91aoz6Ae'
        ]);
    if (!$response->successful()) {
        return response()->json([
            'error' => 'Failed to create instance',
            'status' => $response->status(),
            'body' => $response->body(),
            'json' => $response->json(),
        ], $response->status());
    }
    $data = $response->json();    
    Space_whapichannel_details::create([
        'space_id'          => $space_id,
        'client_id'         => $space->client_id,
        'payment_status'    => 'unpaid',
        'payment_method'    => 'whapi',
        'payment_origin'    => 'dashboard',
        'payment_reference' => null,
        'payment_amount'    => $activation_charge,
        'instance_id'       => $data['id'] ?? null,
        'creationTS'        => $data['creationTS'] ?? 0,
        'ownerId'           => $data['ownerId'] ?? '',
        'activeTill'        => $data['activeTill'] ?? 0,
        'token'             => $data['token'] ?? '',
        'server'            => $data['server'] ?? 0,
        'stopped'           => $data['stopped'] ?? false,
        'status'            => $data['status'] ?? 'inactive',
        'name'              => $data['name'] ?? '',
        'projectId'         => $data['projectId'] ?? '',
        'activation_charge' => $activation_charge
    ]);
    return response()->json([
        'message' => 'Instance created successfully',
        'activation_charge' => $activation_charge,
        'currency' => $currency,
        'data' => $data
    ]);
}

public function fetchQrCode(Request $request)
{
    $validated = $request->validate([
        'space_id' => 'required|exists:Space_whapichannel_details,space_id',
    ], [
        'space_id.exists' => 'The WhatsApp instance for this space has not been created yet.',
        'space_id.required' => 'The space ID is required.',
    ]);
    $instance = Space_whapichannel_details::where('space_id', $validated['space_id'])->first();
    if (!$instance) {
        return response()->json(['error' => 'Instance not found.'], 404);
    }
    if ($instance->payment_status !== 'success') {
        return response()->json([
            'error' => 'Payment not successful.',
            'payment_status' => $instance->payment_status,
        ], 403);
    }
    if (!$instance->token || !$instance->instance_id) {
        return response()->json(['error' => 'Instance token or ID is missing'], 400);
    }
    return $this->getQrCode($instance->instance_id, $instance->token);
}
    
private function getQrCode($instanceId, $token)
    {
        try {
            $instance = Space_whapichannel_details::where('token', $token)->first();
            $response = Http::withToken(env('WHAPI_MASTER_TOKEN'))->get("https://manager.whapi.cloud/channels/{$instanceId}");
            // if ($response->failed()) {
            //     return response()->json([
            //         'success' => false,
            //         'message' => 'Failed to fetch channel details',
            //         'status_code' => $response->status(),
            //         'data' => $response->json(),
            //     ], $response->status());
            // }
            $channel = $response->json();
        // $isPaid = isset($channel['recurrentPaymentId']) &&
        //           ($channel['mode'] ?? null) === 'live' &&
        //           $channel['stopped'] === false;
        // if ($isPaid) {
        //    $instance->_instance_activation_status=1;
        //    $instance->save( );
        // }
        $space_id = $instance->space_id;
        $space = Space::where('id', $space_id)->first();
        if (!$instance) {
            return response()->json([
                'error' => 'Instance not found for the provided token.',
            ], 404);
        }
        $client = new \GuzzleHttp\Client();
        $healthResponse = $client->request('GET', 'https://gate.whapi.cloud/health?wakeup=true&channel_type=web', [
            'headers' => [
                'accept' => 'application/json',
                'authorization' => $token,
            ],
        ]);
        $responseBody = $healthResponse->getBody()->getContents();
        $data = json_decode($responseBody, true);
        $space_phone = $data['user']['id'] ?? null;
        if (isset($data['user']) && !is_null($data['user'])) {
            $space->space_phone = $space_phone;
            $space->save();
            return response()->json([
                'linked' => true,
                'message' => 'Account is already linked.',
                'user' => $data['user'],
                '_whatsapp_linking_status_' => 1
            ]);
        }
        $qrResponse = $client->request('GET', "https://gate.whapi.cloud/users/login/image?wakeup=true", [
            'headers' => [
                'accept' => 'image/png',
                'authorization' => 'Bearer ' . $token,
            ],
        ]);
        if ($qrResponse->getStatusCode() === 200) {
            $instance->_whatsapp_linking_status = 0;
            $instance->save();
            return response($qrResponse->getBody()->getContents(), 200)->header('Content-Type', 'image/png');
        }
        logger()->error('Whapi QR fetch failed', [
            'status' => $qrResponse->getStatusCode(),
            '_whatsapp_linking_status_' => 0,
            'body' => $qrResponse->getBody()->getContents(),
        ]);
        return response()->json([
            'error' => 'Failed to fetch QR',
            'status' => $qrResponse->getStatusCode(),
            'response' => $qrResponse->getBody()->getContents()
        ], 500);
    } catch (\Exception $e) {
        logger()->error('Exception while fetching QR', [
            'message' => $e->getMessage()
        ]);
        return response()->json([
            'error' => 'Exception while fetching QR',
            'message' => $e->getMessage()
        ], 500);
    }
}
public function instance_activation_status(Request $request)
{
    $validated = $request->validate([
        'space_id' => 'required|exists:Space_whapichannel_details,space_id',
    ]);
    $instance_activation_status=Space_whapichannel_details::where('space_id',$validated['space_id'])->value('_instance_activation_status');
    return response()->json([
        'status'=>true,
        'instance_activation_status'=>$instance_activation_status,
        'message'=>'instance activation status fetched successfully' 
    ]);
}

public function run_agent($uuid)
{
    $space = Space::where('uuid', $uuid)->firstOrFail();
    $space_whapichannel_details = Space_whapichannel_details::where('space_id', $space->id)->first();

    if (!$space_whapichannel_details) {
        $space_whapichannel_details = Space_whapichannel_details::create([
            'space_id'       => $space->id,
            'client_id'      => $space->client_id,
            'payment_status' => 'pending',
        ]);
    }
    if ($space_whapichannel_details->payment_status !== 'success') {
        return redirect()->route('payment.instance.options', ['uuid' => $uuid]);
    }
    if (!$space_whapichannel_details->instance_id) {
        $name = $space->name . '-' . $space->chatbot_name;
        $response = Http::withToken(env('WHAPI_MASTER_TOKEN'))
            ->put('https://manager.whapi.cloud/channels', [
                'name'      => $name,
                'projectId' => 'TSu2uo9AC9nu91aoz6Ae',
            ]);
        if (!$response->successful()) {
            return response()->json([
                'error'  => 'Failed to create WHAPI instance',
                'status' => $response->status(),
                'body'   => $response->body(),
                'json'   => $response->json(),
            ], $response->status());
        }
        $instanceData = $response->json();
        $space_whapichannel_details->update([
            'instance_id' => $instanceData['id'] ?? null,
            'creationTS'  => $instanceData['creationTS'] ?? null,
            'ownerId'     => $instanceData['ownerId'] ?? null,
            'activeTill'  => $instanceData['activeTill'] ?? null,
            'token'       => $instanceData['token'] ?? null,
            'server'      => $instanceData['server'] ?? null,
            'status'      => $instanceData['status'] ?? null,
            'name'        => $instanceData['name'] ?? null,
            'projectId'   => $instanceData['projectId'] ?? null,
        ]);
        $isPaid = isset($instanceData['recurrentPaymentId']) &&
                 ($instanceData['mode'] ?? null) === 'live' &&
                 $instanceData['stopped'] === false;
        $space_whapichannel_details->_instance_activation_status = $isPaid ? 1 : 0;
        $space_whapichannel_details->save();
    }
    if ($space_whapichannel_details->_instance_activation_status == 1) {
        $client = new \GuzzleHttp\Client();
        $healthResponse = $client->request('GET', 'https://gate.whapi.cloud/health?wakeup=true&channel_type=web', [
            'headers' => [
                'accept' => 'application/json',
                'authorization' => $space_whapichannel_details->token,
            ],
        ]);
        $healthData = json_decode($healthResponse->getBody()->getContents(), true);
        $space_phone = $healthData['user']['id'] ?? null;
        if (!empty($healthData['user'])) {
            $space->space_phone = $space_phone;
            $space->save();

            return response()->json([
                'linked' => true,
                'message' => 'Account is already linked.',
                'user' => $healthData['user'],
                '_whatsapp_linking_status_' => 1
            ]);
        }
        $qrResponse = $client->request('GET', "https://gate.whapi.cloud/users/login/image?wakeup=true", [
            'headers' => [
                'accept' => 'image/png',
                'authorization' => 'Bearer ' . $space_whapichannel_details->token,
            ],
        ]);

        if ($qrResponse->getStatusCode() === 200) {
            $space_whapichannel_details->_whatsapp_linking_status = 0;
            $space_whapichannel_details->save();

            return response($qrResponse->getBody()->getContents(), 200)
                ->header('Content-Type', 'image/png');
        }
    }
    return response()->json([
        'message' => 'Instance activation in progress. Please wait...',
    ]);
}



}



