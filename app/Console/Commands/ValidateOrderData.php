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

        $errorRecord = $this->orderValidationLogicService->validateOrder($order['data']);

        return $this->processErrorRecord($errorRecord, $orderID);
    }

    private function processErrorRecord($errorRecord, string $orderID): int
    {
        if ($this->hasErrors($errorRecord)) {
            $errorRecord->err_count++;
            $errorRecord->save();
            $this->error('Errors found during validation.');
            return self::FAILURE;
        } else {
            $this->info('No errors found after validation.');
            return self::SUCCESS;
        }
    }

    private function logAndReturnError(string $message, string $orderID = ''): int
    {
        Log::error($message, ['orderID' => $orderID]);
        $this->error($message);
        return self::FAILURE;
    }


    private function hasErrors($errorRecord): bool
    {
        return $errorRecord->err_loadid === 1 ||
            $errorRecord->err_client === 1 ||
            $errorRecord->err_amount === 1 ||
            $errorRecord->err_attach === 1 ||
            $errorRecord->err_pickaddress === 1 ||
            $errorRecord->err_deladdress === 1 ||
            $errorRecord->err_email === 1 ||
            $errorRecord->err_pickbol === 1 ||
            $errorRecord->err_method === 1;
    }
}
