<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class DailyController extends Controller
{
    /**
     * Crear una sala en Daily mediante su API y devolver la URL.
     */
    public function store(Request $request)
    {
        $apiKey = env('DAILY_API_KEY') ?: config('services.daily.key');
        if (!$apiKey) {
            return response()->json(['error' => 'Daily API key not configured'], 500);
        }

        // Generar nombre si no se envía uno
        $name = $request->input('name') ?? ('room-' . time() . '-' . uniqid());

        try {
            // En Doomcloud NO uses cacert de Windows
            $resp = Http::withOptions(['verify' => true])
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type' => 'application/json',
                ])->post('https://api.daily.co/v1/rooms', [
                    'name' => $name,
                    'properties' => [
                        'exp' => null,
                    ],
                ]);

            if (!$resp->successful()) {
                return response()->json([
                    'error' => 'Daily API error',
                    'detail' => $resp->body()
                ], $resp->status());
            }

            $body = $resp->json();

            return response()->json([
                'url' => $body['url'] ?? null,
                'name' => $body['name'] ?? null,
                'raw' => $body,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Exception creating daily room',
                'detail' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crear un meeting token para unirse a una sala.
     */
    public function createToken(Request $request)
    {
        $apiKey = env('DAILY_API_KEY');
        if (!$apiKey) {
            return response()->json(['error' => 'Daily API key not configured'], 500);
        }

        $name = $request->input('name');
        $owner = $request->input('owner') ? true : false;

        if (!$name) {
            return response()->json(['error' => 'Missing room name'], 400);
        }

        try {
            $body = [
                'properties' => [
                    'room_name' => $name,
                    'is_owner' => $owner,
                ],
            ];

            $resp = Http::withOptions(['verify' => true])
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type' => 'application/json',
                ])->post('https://api.daily.co/v1/meeting-tokens', $body);

            if (!$resp->successful()) {
                return response()->json([
                    'error' => 'Daily API error',
                    'detail' => $resp->body()
                ], $resp->status());
            }

            return response()->json($resp->json());

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Exception creating meeting token',
                'detail' => $e->getMessage()
            ], 500);
        }
    }
}
