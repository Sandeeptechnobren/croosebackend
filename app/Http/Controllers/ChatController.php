<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ChatController extends Controller
{
    public function chat(Request $request)
    {
        $request->validate([
            'message' => 'required|string'
        ]);

        $clientId = Auth::check() ? Auth::id() : null;
        $sessionId = $request->input('session_id') ?? Str::uuid()->toString();
        $query = DB::table('chat_messages');
        if ($clientId) {
            $query->where('client_id', $clientId);
        } else {
            $query->where('session_id', $sessionId);
        }
        $messages = $query
            ->orderBy('id', 'desc')
            ->limit(10)
            ->get()
            ->reverse();
        $history = [];
        foreach ($messages as $msg) {
            $history[] = [
                'role' => $msg->role,
                'parts' => [
                    ['text' => $msg->message]
                ]
            ];
        }
        $history[] = [
            'role' => 'user',
            'parts' => [
                ['text' => $request->message]
            ]
        ];

        $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent'
             . '?key=' . env('GEMINI_API_KEY');

        $response = Http::post($url, [
            'contents' => $history
        ]);

        if (!$response->successful()) {
            return response()->json([
                'error' => 'Gemini API error',
                'details' => $response->json()
            ], 500);
        }

        $reply = $response->json('candidates.0.content.parts.0.text');
        DB::table('chat_messages')->insert([
            'client_id' => $clientId,
            'session_id' => $sessionId,
            'role' => 'user',
            'message' => $request->message,
            'created_at' => now()
        ]);

        DB::table('chat_messages')->insert([
            'client_id' => $clientId,
            'session_id' => $sessionId,
            'role' => 'model',
            'message' => $reply,
            'created_at' => now()
        ]);
        return response()->json([
            'reply' => $reply,
            // 'session_id' => $sessionId,
            // 'client_id' => $clientId,
            // 'memory_used' => count($history)
        ]);
    }
}
