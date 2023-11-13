<?php

namespace App\Console\Commands;

use App\Models\Error;
use App\Services\SuperDispatchService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ValidateOrderData extends Command
{
    protected $signature = 'validate:order-data {orderID}';
    protected $description = 'Fetch and validate order data JSON';
    private SuperDispatchService $superDispatchService;

    public function __construct(SuperDispatchService $superDispatchService)
    {
        parent::__construct();
        $this->superDispatchService = $superDispatchService;
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

        return $this->processOrderValidation($order['data'], $orderID);
    }

    private function processOrderValidation(array $order, string $orderID): int
    {
        try {
            $errorRecord = $this->validateOrder($order);
            $this->saveErrorRecord($errorRecord, $orderID);
            $this->info('Order data validated successfully.');
            return self::SUCCESS;
        } catch (\Exception $e) {
            return $this->logAndReturnError('JSON structure error: ' . $e->getMessage(), $orderID);
        }
    }

    private function validateOrder(array $order): Error
    {
        $errorRecord = Error::firstOrNew(['order_id' => $order['id']]);
        $this->setValidationFlags($errorRecord, $order);
        return $errorRecord;
    }

    private function setValidationFlags(Error $errorRecord, array $order): void
    {
        $errorRecord->fill([
            'err_loadid' => $this->isInvalid($order['vehicles'][0]['id'] ?? null),
            'err_client' => $this->isInvalid($order['customer']['name'] ?? null),
            'err_amount' => $order['price'] < 100,
            'err_attach' => $this->isInvalid($order['pdf_bol_url'] ?? null),
            'err_pickaddress' => $this->isAddressInvalid($order['pickup']['venue'] ?? []),
            'err_deladdress' => $this->isAddressInvalid($order['delivery']['venue'] ?? []),
            'err_email' => !$this->hasEmail($order['internal_notes'] ?? []),
            'err_pickbol' => count($order['vehicles'][0]['photos'] ?? []) < 20,
            'err_method' => $this->hasPaymentMethodError($order['vehicles'] ?? [])
        ]);
    }

    private function isInvalid($value): bool
    {
        return empty($value);
    }

    private function isAddressInvalid(array $venue): bool
    {
        return empty($venue['state']) || empty($venue['zip']);
    }

    private function saveErrorRecord(Error $errorRecord, string $orderID): void
    {
        if ($this->hasErrors($errorRecord)) {
            $errorRecord->err_count++;
            $errorRecord->save();
            $this->error('Errors found during validation.');
        } else {
            $this->info('No errors found after validation.');
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

    private function hasPaymentMethodError(array $vehicles): bool {
        $paymentMethods = ['Factoring', 'Other', 'Comcheck', 'ACH'];
        foreach ($vehicles as $vehicle) {
            foreach ($paymentMethods as $method) {
                if (isset($vehicle[$method])) {
                    $this->error("Payment method {$method} should not be present in vehicle ID {$vehicle['id']}.");
                    return true;
                }
            }
        }
        return false;
    }

    private function hasEmail(array $notes): bool
    {
        foreach ($notes as $note) {
            if (filter_var($note, FILTER_VALIDATE_EMAIL)) {
                return true;
            }
        }
        return false;
    }
}
