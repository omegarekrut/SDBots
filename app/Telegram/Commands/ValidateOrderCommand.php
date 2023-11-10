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

        // Fetch validation results from the database
        $validationResults = DB::table('errors')->where('order_id', $orderID)->first();
        $message = $validationResults ? $this->formatValidationResults($validationResults) : "No errors found for Order ID: {$orderID}";

        // Log the validation results
        Log::info('Validation results', [
            'orderID' => $orderID,
            'validationResults' => $validationResults
        ]);

        // Reply with the validation results message
        $this->replyWithMessage(['text' => $message]);
    }

    protected function formatValidationResults($results): string
    {
        $formattedMessage = "Validation Results for Order ID: {$results->order_id}\n";

        foreach ($results as $key => $value) {
            if (str_starts_with($key, 'err_') && $key !== 'err_count' && $value == 1) {
                $formattedMessage .= ucfirst(str_replace('_', ' ', $key)) . ": Failed\n";
            }
        }

        return $formattedMessage;
    }
}
