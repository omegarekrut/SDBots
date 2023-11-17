<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class TelegramValidationMessageService
{
    public function formatValidationResults($results, string $orderID, string $carrierName): string
    {
        if (empty($results)) {
            return "✅ No errors found for Order ID: {$orderID}";
        }

        $formattedMessage = "🔍 Validation results for Order ID: {$results->order_id}\n\n⚡️⚡️⚡️\n\nCompany name: {$carrierName}";
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
