<?php

namespace App\Telegram\Commands;

use App\Services\TelegramValidationMessageService;
use Illuminate\Support\Facades\App;
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
        Log::info('Fetched validation results', ['orderID' => $orderID, 'validationResults' => $validationResults]);

        $messageFormatter = App::make(TelegramValidationMessageService::class);
        $message = $messageFormatter->formatValidationResults($validationResults, $orderID);

        $this->replyWithMessage([
            'text' => $message,
            'parse_mode' => 'MarkdownV2'
        ]);
    }

    private function fetchValidationResults(string $orderID): ?object
    {
        return DB::table(self::ERRORS_TABLE)->where('order_id', $orderID)->first();
    }
}
