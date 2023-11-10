<?php

namespace App\Telegram\Commands;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use stdClass;
use Telegram\Bot\Commands\Command;

class ValidateOrderCommand extends Command
{
    protected string $name = "validate";
    protected string $description = "Validate order data";

    public function handle(): void
    {
        $orderID = $this->extractOrderID($this->getUpdate()->getMessage()->getText(true));
        Log::info('ValidateOrderCommand started', ['orderID' => $orderID]);

        $validationResults = $this->fetchValidationResults($orderID);

        if (!$validationResults) {
            Log::info('No validation results found, executing validate:order-data command', ['orderID' => $orderID]);
            Artisan::call('validate:order-data', ['orderID' => $orderID]);
            $validationResults = $this->fetchValidationResults($orderID);
        }

        Log::info('Fetched validation results', [
            'orderID' => $orderID,
            'validationResults' => $validationResults
        ]);

        $message = $validationResults ? $this->formatValidationResults($validationResults) : "No errors found for Order ID: {$orderID}";
        $this->replyWithMessage(['text' => $message]);
    }

    private function extractOrderID(string $messageText): string
    {
        return trim(str_replace('/validate', '', $messageText));
    }

    private function fetchValidationResults(string $orderID): \Illuminate\Database\Eloquent\Model|stdClass|\Illuminate\Database\Query\Builder|null
    {
        return DB::table('errors')->where('order_id', $orderID)->first();
    }

    protected function formatValidationResults($results): string
    {
        $formattedMessage = "Validation results for Order ID: {$results->order_id}\n\n";

        $errorMessages = [
            'err_loadid' => 'Empty or NULL Load ID',
            'err_client' => 'There is no client',
            'err_amount' => 'Price less than 100',
            'err_attach' => 'PDF BOL URL is missing or empty',
            'err_pickaddress' => 'Pickup address state or zip is missing',
            'err_deladdress' => 'Delivery address state or zip is missing',
            'err_email' => 'No email found in internal notes',
            'err_pickbol' => 'Less than 20 photos in vehicle data',
            'err_method' => 'Invalid payment method in vehicle data)'
        ];

        foreach ($results as $key => $value) {
            if (str_starts_with($key, 'err_') && $key !== 'err_count' && $value == 1) {
                $formattedMessage .= "{$errorMessages[$key]}: Failed\n";
            }
        }

        if (trim($formattedMessage) == "Validation Results for Order ID: {$results->order_id}\n\n") {
            $formattedMessage = "No errors found for Order ID: {$results->order_id}";
        }

        return $formattedMessage;
    }
}
