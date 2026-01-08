<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MessageService
{
    public static function send(string $phone, string $message): bool
    {
        if (empty($phone)) {
            Log::error('WHAPI: phone missing');
            return false;
        }

        // number clean (India)
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
}
