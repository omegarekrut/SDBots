<?php

namespace App\Services;

use App\Models\Error;

class TelegramValidationMessageService
{
    private ErrorMessageService $errorMessageService;
    private MarkdownFormatter $markdownFormatter;

    public function __construct(ErrorMessageService $errorMessageService, MarkdownFormatter $markdownFormatter)
    {
        $this->errorMessageService = $errorMessageService;
        $this->markdownFormatter = $markdownFormatter;
    }

    public function formatValidationResults(Error $errorObject, string $orderID, string $carrierName, string $orderNumber, string $carModelMake): string
    {
        $formattedMessage = $this->buildInitialMessage($errorObject, $carrierName, $orderNumber, $carModelMake);
        $formattedMessage .= $this->buildErrorMessages($errorObject);

        return $this->appendErrorMessageOrFinalize($formattedMessage, $errorObject);
    }

    private function buildInitialMessage(Error $errorObject, string $carrierName, string $orderNumber, string $carModelMake): string
    {
        return sprintf(
            "🔍 Validation results:\n\n⚡️⚡️⚡️\n\n🏢 *Carrier name:* %s\n📄 *Load ID:* `%s`\n🚘 *Car: * %s\n",
            $carrierName,
            $this->markdownFormatter->escape($orderNumber),
            $this->markdownFormatter->escape($carModelMake)
        );
    }

    private function buildErrorMessages(Error $errorObject): string
    {
        $errorMessages = $this->errorMessageService->getErrorMessages();
        $message = '';

        foreach ($errorObject->getAttributes() as $key => $value) {
            if ($this->isValidationErrorKey($key, $value)) {
                $message .= "\n{$errorMessages[$key]}: ❌ Failed";
            }
        }

        return $message;
    }

    private function isValidationErrorKey(string $key, $value): bool
    {
        return str_starts_with($key, 'err_') && $key !== 'err_count' && $value == 1;
    }

    private function appendErrorMessageOrFinalize(string $message, Error $results): string
    {
        if (!empty($results->error_message)) {
            return $message . "\nError Message: " . $results->error_message;
        }

        return trim($message) === "Validation results for Order ID: {$results->order_id}\n\n" ?
            "No errors found for Order ID: {$results->order_id}" : $message;
    }
}

class MarkdownFormatter
{
    public function escape(string $text): string
    {
        $search = ['_', '*', '[', ']', '(', ')', '~', '>', '#', '+', '-', '=', '|', '{', '}', '.', '!'];
        $replace = array_map(fn($char) => "\\$char", $search);

        return str_replace($search, $replace, $text);
    }
}
