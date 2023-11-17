<?php

namespace App\Services;

use App\Models\Error;
use Illuminate\Support\Facades\Log;

class TelegramValidationMessageService
{
    public function formatValidationResults(Error $errorObject, string $orderID, string $carrierName, string $orderNumber): string
    {
        $formattedMessage = "🔍 Validation results for Order ID: {$errorObject->order_id}\n\n⚡️⚡️⚡️\n\n🏢 *Carrier name:* {$carrierName}\n📄 *Load ID:* {$this->escapeMarkdownV2Characters($orderNumber)}";
        $errorMessages = ErrorMessageService::getErrorMessages();

        foreach ($errorObject->getAttributes() as $key => $value) {
            if ($this->isValidationErrorKey($key, $value)) {
                $formattedMessage .= "\n{$errorMessages[$key]}: ❌ Failed";
            }
        }

        return $this->appendErrorMessageOrFinalize($formattedMessage, $errorObject);
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

    private function escapeMarkdownV2Characters(string $text): string
    {
        return str_replace(
            ['_', '*', '[', ']', '(', ')', '~', '`', '>', '#', '+', '-', '=', '|', '{', '}', '.', '!'],
            ['\_', '\*', '\[', '\]', '\(', '\)', '\~', '\`', '\>', '\#', '\+', '\-', '\=', '\|', '\{', '\}', '\.', '\!'],
            $text
        );
    }
}
