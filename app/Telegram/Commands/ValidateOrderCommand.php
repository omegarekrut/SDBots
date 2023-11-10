<?php

namespace App\Telegram\Commands;

use App\Services\ErrorMessageService;
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
        $orderID = $this->extractOrderID($this->getUpdate()->getMessage()->getText(true));
        Log::info('ValidateOrderCommand started', ['orderID' => $orderID]);
        Log::info('Executing validate:order-data command', ['orderID' => $orderID]);

        Artisan::call('validate:order-data', ['orderID' => $orderID]);

        $validationResults = $this->fetchValidationResults($orderID);
        Log::info('Fetched validation results', [
            'orderID' => $orderID,
            'validationResults' => $validationResults
        ]);

        $message = $validationResults ? $this->formatValidationResults($validationResults) : "No errors found for Order ID: {$orderID}";

        if (!empty($validationResults->error_message)) {
            $message .= "\nJSON Error: " . $validationResults->error_message;
        }

        $this->replyWithMessage(['text' => $message]);
    }

    private function extractOrderID(string $messageText): string
    {
        return trim(str_replace('/validate', '', $messageText));
    }

    private function fetchValidationResults(string $orderID)
    {
        return DB::table('errors')->where('order_id', $orderID)->first();
    }

    protected function formatValidationResults($results): string
    {
        $formattedMessage = "Validation results for Order ID: {$results->order_id}\n\n";

        $errorMessages = ErrorMessageService::getErrorMessages();

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
