<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class DailyController extends Controller
{
    /**
     * Crear una sala en Daily mediante su API y devolver la URL.
     * El endpoint está protegido por el middleware de autenticación por token.
     */
    public function store(Request $request)
    {
        $apiKey = env('DAILY_API_KEY');
        if (!$apiKey) {
            return response()->json(['error' => 'Daily API key not configured'], 500);
        }

        // Nombre opcional para la sala; generar uno por defecto si no se proporciona
        $name = $request->input('name') ?? ('room-' . time() . '-' . uniqid());

        try {
            // Usar verificación TLS usando un bundle de CAs local (mejor que withoutVerifying)
            // Ajusta la ruta si colocas cacert.pem en otra ubicación.
            $resp = Http::withOptions(['verify' => 'D:\\Xampp\\php\\extras\\ssl\\cacert.pem'])
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type' => 'application/json',
                ])->post('https://api.daily.co/v1/rooms', [
                'name' => $name,
                // ajustar propiedades por defecto si se desea
                'properties' => [
                    'exp' => null,
                ],
            ]);

            if (!$resp->successful()) {
                return response()->json(['error' => 'Daily API error', 'detail' => $resp->body()], $resp->status());
            }

            $body = $resp->json();
            // Daily devuelve 'url' y 'name' entre otros
            return response()->json([
                'url' => $body['url'] ?? null,
                'name' => $body['name'] ?? null,
                'raw' => $body,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Exception creating daily room', 'detail' => $e->getMessage()], 500);
        }
    }

    /**
     * Crear un meeting token para unirse a una sala. Espera JSON { name: 'room-name', owner: true|false }
     * Devuelve el body entero de Daily (contiene el token en la respuesta).
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

            $resp = Http::withOptions(['verify' => 'D:\\Xampp\\php\\extras\\ssl\\cacert.pem'])
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type' => 'application/json',
                ])->post('https://api.daily.co/v1/meeting-tokens', $body);

            if (!$resp->successful()) {
                return response()->json(['error' => 'Daily API error', 'detail' => $resp->body()], $resp->status());
            }

            return response()->json($resp->json());
        } catch (\Exception $e) {
            return response()->json(['error' => 'Exception creating meeting token', 'detail' => $e->getMessage()], 500);
        }
    }
}
