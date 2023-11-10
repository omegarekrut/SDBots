<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Services\SuperDispatchService;

class FetchOrder extends Command
{
    protected $signature = 'fetch:order {orderId}';
    protected $description = 'Fetch a single order by ID';
    private SuperDispatchService $superDispatchService;

    public function __construct(SuperDispatchService $superDispatchService)
    {
        parent::__construct();
        $this->superDispatchService = $superDispatchService;
    }

    public function handle(): int
    {
        $orderId = $this->argument('orderId');
        $accessToken = $this->getAccessToken();

        if (!$accessToken) {
            $this->error('Failed to obtain access token.');
            return self::FAILURE;
        }

        $order = $this->fetchOrder($orderId, $accessToken);

        if (!$order) {
            $this->error('Failed to fetch the order.');
            return self::FAILURE;
        }

        $this->info('Order fetched successfully.');
        $this->line(json_encode($order, JSON_PRETTY_PRINT));

        return self::SUCCESS;
    }

    private function fetchOrder(string $orderId, string $accessToken): ?array
    {
        $response = Http::withHeaders([
            'Authorization' => "Bearer {$accessToken}",
            'Content-Type' => 'application/json'
        ])->get("https://carrier.superdispatch.com/v1/orders/{$orderId}/");

        return $response->successful() ? $response->json() : null;
    }
}
