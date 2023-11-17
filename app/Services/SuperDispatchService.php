<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class SuperDispatchService
{
    public function getAccessToken(): ?string
    {
        $response = Http::asForm()->post('https://carrier.superdispatch.com/oauth/token/', [
            'grant_type' => 'client_credentials',
            'client_id' => env('SUPERDISPATCH_CLIENT_ID'),
            'client_secret' => env('SUPERDISPATCH_CLIENT_SECRET')
        ]);

        return $response->successful() ? $response->json()['access_token'] : null;
    }

    public function fetchOrder(string $orderID, string $accessToken): ?array
    {
        $response = Http::withHeaders([
            'Authorization' => "Bearer {$accessToken}",
            'Content-Type' => 'application/json'
        ])->get("https://carrier.superdispatch.com/v1/orders/{$orderID}/");

        return $response->successful() ? $response->json() : null;
    }

    public function fetchAttachments(string $orderID, string $accessToken): ?array
    {
        $response = Http::withHeaders([
            'Authorization' => "Bearer {$accessToken}",
            'Content-Type' => 'application/json'
        ])->get("https://carrier.superdispatch.com/orders/{$orderID}/attachments/");

        return $response->successful() ? $response->json() : null;
    }
}
