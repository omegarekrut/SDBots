<?php

namespace App\Services;

use App\Models\SuperDispatchConfig;
use Illuminate\Support\Facades\Http;

class SuperDispatchService
{
    public function getAccessToken(string $carrierName): ?string
    {
        $config = SuperDispatchConfig::where('api_name', $carrierName)->first();

        if (!$config) {
            return null;
        }

        $response = Http::asForm()->post('https://carrier.superdispatch.com/oauth/token/', [
            'grant_type' => 'client_credentials',
            'client_id' => $config->client_id,
            'client_secret' => $config->client_secret
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
        ])->get("https://carrier.superdispatch.com/v1/orders/{$orderID}/attachments/");

        return $response->successful() ? $response->json() : null;
    }
}
