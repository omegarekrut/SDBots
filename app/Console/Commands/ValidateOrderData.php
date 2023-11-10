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
            $this->error('Failed to obtain access token.');
            return self::FAILURE;
        }

        $order = $this->superDispatchService->fetchOrder($orderID, $accessToken);

        if (!$order) {
            $this->error('Failed to fetch order data.');
            return self::FAILURE;
        }

        try {
            $this->validateOrder($order['data']);
            $this->info('Order data validated successfully.');

            return self::SUCCESS;
        } catch (\Exception $e) {
            Log::error('JSON structure error: ' . $e->getMessage(), ['orderID' => $orderID]);
            $this->error('JSON structure error: ' . $e->getMessage());

            $errorRecord = Error::firstOrNew(['order_id' => $orderID]);
            $errorRecord->error_message = $e->getMessage();
            $errorRecord->save();

            return self::FAILURE;
        }
    }

    private function validateOrder(array $order): void
    {
        $errorRecord = Error::firstOrNew(['order_id' => $order['id']]);
        $hasErrors = false;

        $errorRecord->err_loadid = $this->setErrorFlag($errorRecord->err_loadid, empty($order['vehicles'][0]['id']));
        $errorRecord->err_client = $this->setErrorFlag($errorRecord->err_client, empty($order['customer']['name']));
        $errorRecord->err_amount = $this->setErrorFlag($errorRecord->err_amount, $order['price'] < 100);
        $errorRecord->err_attach = $this->setErrorFlag($errorRecord->err_attach, empty($order['pdf_bol_url']));
        $errorRecord->err_pickaddress = $this->setErrorFlag($errorRecord->err_pickaddress, empty($order['pickup']['venue']['state']) || empty($order['pickup']['venue']['zip']));
        $errorRecord->err_deladdress = $this->setErrorFlag($errorRecord->err_deladdress, empty($order['delivery']['venue']['state']) || empty($order['delivery']['venue']['zip']));
        $errorRecord->err_email = $this->setErrorFlag($errorRecord->err_email, !empty($order['internal_notes']) && !$this->hasEmail($order['internal_notes']));
        $errorRecord->err_pickbol = $this->setErrorFlag($errorRecord->err_pickbol, count($order['vehicles'][0]['photos']) < 20);

        $errorRecord->err_method = $this->setErrorFlag($errorRecord->err_method, $this->hasPaymentMethodError($order['vehicles']));

        $errorRecord->err_count = $errorRecord->err_count + 1;

        $hasErrors = $this->hasErrors($errorRecord);

        if ($hasErrors) {
            $errorRecord->err_count = $errorRecord->err_count + 1;
            $errorRecord->save();
            $this->error('Errors found during validation.');
        } else {
            $this->info('No errors found after validation.');
        }
    }

    private function setErrorFlag($previousFlag, $currentErrorCondition): int
    {
        if ($currentErrorCondition) {
            return 1;
        } elseif ($previousFlag === 1) {
            return 2;
        }
        return 0; // No error
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
