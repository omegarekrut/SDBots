<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Symfony\Component\Console\Command\Command as CommandAlias;

class FetchOrdersList extends Command
{
    protected $signature = 'fetch:orders-list';
    protected $description = 'Fetch the first list of orders with status "delivered"';

    public function handle(): int
    {
        $accessToken = $this->getAccessToken();

        if (!$accessToken) {
            $this->error('Failed to obtain access token.');
            return CommandAlias::FAILURE;
        }

        $ordersList = $this->fetchOrdersList($accessToken);

        if (!$ordersList) {
            $this->error('Failed to fetch the orders list.');
            return CommandAlias::FAILURE;
        }

        // Process the orders list as needed
        $this->info('Orders list fetched successfully.');
        $this->line(json_encode($ordersList, JSON_PRETTY_PRINT));

        return CommandAlias::SUCCESS;
    }

    private function getAccessToken(): ?string
    {
        $response = Http::asForm()->post('https://carrier.superdispatch.com/oauth/token/', [
            'grant_type' => 'client_credentials',
            'client_id' => env('SUPERDISPATCH_CLIENT_ID'),
            'client_secret' => env('SUPERDISPATCH_CLIENT_SECRET')
        ]);

        return $response->successful() ? $response->json()['access_token'] : null;
    }

    private function fetchOrdersList(string $accessToken): ?array
    {
        $response = Http::withHeaders([
            'Authorization' => "Bearer {$accessToken}",
            'Content-Type' => 'application/json'
        ])->get("https://carrier.superdispatch.com/v1/orders?status=delivered");

        return $response->successful() ? $response->json()['data'] : null;
    }
}
