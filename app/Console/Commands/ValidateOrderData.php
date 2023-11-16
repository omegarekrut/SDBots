<?php

namespace App\Console\Commands;

use App\Models\Error;
use App\Services\OrderValidationLogicService;
use App\Services\SuperDispatchService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ValidateOrderData extends Command
{
    protected $signature = 'validate:order-data {orderID}';
    protected $description = 'Fetch and validate order data JSON';

    private SuperDispatchService $superDispatchService;
    private OrderValidationLogicService $orderValidationLogicService;


    public function __construct(SuperDispatchService $superDispatchService, OrderValidationLogicService $orderValidationLogicService)
    {
        parent::__construct();
        $this->superDispatchService = $superDispatchService;
        $this->orderValidationLogicService = $orderValidationLogicService;
    }

    public function handle(): int
    {
        $orderID = $this->argument('orderID');
        $accessToken = $this->superDispatchService->getAccessToken();

        if (!$accessToken) {
            return $this->logAndReturnError('Failed to obtain access token.');
        }

        $order = $this->superDispatchService->fetchOrder($orderID, $accessToken);

        if (!$order || !isset($order['data'])) {
            return $this->logAndReturnError('Failed to fetch order data or order data is incomplete.', $orderID);
        }

        try {
            $errorRecord = $this->orderValidationLogicService->validateOrder($order['data']);
            $this->handleValidationResult($errorRecord);
            return self::SUCCESS;
        } catch (\Exception $e) {
            return $this->logAndReturnError('Validation error: ' . $e->getMessage(), $orderID);
        }
    }

    private function handleValidationResult($errorRecord): void
    {
        if ($errorRecord->hasErrors()) {
            $this->error('Errors found during validation.');
        } else {
            $this->info('Order data validated successfully.');
        }
    }

    private function logAndReturnError(string $message, string $orderID = ''): int
    {
        Log::error($message, ['orderID' => $orderID]);
        $this->error($message);
        return self::FAILURE;
    }
}
