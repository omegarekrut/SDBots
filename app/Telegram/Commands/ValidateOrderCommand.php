<?php

namespace App\Telegram\Commands;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Commands\Command;

class ValidateOrderCommand extends Command
{
    protected string $name = "validate";
    protected string $description = "Validate order data";

    public function handle(): void
    {
        $messageText = $this->getUpdate()->getMessage()->getText(true);
        $orderID = trim(str_replace('/validate', '', $messageText));

        Log::info('ValidateOrderCommand started', ['orderID' => $orderID]);

        $validationResults = DB::table('errors')->where('order_id', $orderID)->first();
        $message = $validationResults ? $this->formatValidationResults($validationResults) : "No errors found for Order ID: {$orderID}";

        Log::info('Validation results', [
            'orderID' => $orderID,
            'validationResults' => $validationResults
        ]);

        $this->replyWithMessage(['text' => $message]);
    }

    protected function formatValidationResults($results): string
    {
        $formattedMessage = "Validation Results for Order ID: {$results->order_id}\n\n";

        $errorMessages = [
            'err_loadid' => 'Empty or NULL Load ID (data->vehicles->id)',
            'err_client' => 'There is no client (data->customer->name is empty)',
            'err_amount' => 'Price less than 100 (data->price)',
            'err_attach' => 'PDF BOL URL is missing or empty (pdf_bol_url)',
            'err_pickaddress' => 'Pickup address state or zip is missing (pickup->venue->state/zip)',
            'err_deladdress' => 'Delivery address state or zip is missing (delivery->venue->state/zip)',
            'err_email' => 'No email found in internal notes (data->internal_notes)',
            'err_pickbol' => 'Less than 20 photos in vehicle data (data->vehicles->photos)',
            'err_method' => 'Invalid payment method in vehicle data (data->vehicles->[Factoring, Other, Comcheck, ACH])'
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
