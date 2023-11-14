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

    const ERRORS_TABLE = 'errors';

    public function handle(): void
    {
        $orderID = $this->extractOrderID();
        Log::info('ValidateOrderCommand started', ['orderID' => $orderID]);

        if ($this->validateOrderData($orderID)) {
            $this->replyWithValidationResults($orderID);
        } else {
            $this->replyWithMessage(['text' => "❌ Failed to validate order data for Order ID: {$orderID}"]);
        }
    }

    private function extractOrderID(): string
    {
        return trim(str_replace('/validate', '', $this->getUpdate()->getMessage()->getText(true)));
    }

    private function validateOrderData(string $orderID): bool
    {
        return Artisan::call('validate:order-data', ['orderID' => $orderID]) === 0;
    }

    private function replyWithValidationResults(string $orderID): void
    {
        $validationResults = $this->fetchValidationResults($orderID);
        Log::info('Fetched validation results', [
            'orderID' => $orderID,
            'validationResults' => $validationResults
        ]);

        $message = $this->formatValidationResults($validationResults, $orderID);
        $this->replyWithMessage(['text' => $message]);
    }

    private function fetchValidationResults(string $orderID): ?object
    {
        return DB::table(self::ERRORS_TABLE)->where('order_id', $orderID)->first();
    }

    protected function formatValidationResults($results, string $orderID): string
    {
        if (empty($results)) {
            return "✅ No errors found for Order ID: {$orderID}";
        }

        $formattedMessage = "🔍 Validation results for Order ID: {$results->order_id}\n⚡️⚡️⚡️\n";
        $errorMessages = ErrorMessageService::getErrorMessages();

        foreach ($results as $key => $value) {
            if ($this->isValidationErrorKey($key, $value)) {
                $formattedMessage .= "{$errorMessages[$key]}: ❌ Failed\n";
            }
        }

        return $this->appendErrorMessageOrFinalize($formattedMessage, $results);
    }

    private function isValidationErrorKey(string $key, $value): bool
    {
        return str_starts_with($key, 'err_') && $key !== 'err_count' && $value == 1;
    }

    private function appendErrorMessageOrFinalize(string $message, $results): string
    {
        if (!empty($results->error_message)) {
            return $message . "\nError Message: " . $results->error_message;
        }

        return trim($message) == "Validation results for Order ID: {$results->order_id}\n\n" ?
            "No errors found for Order ID: {$results->order_id}" : $message;
    }
}
