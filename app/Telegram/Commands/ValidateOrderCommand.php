<?php

namespace App\Telegram\Commands;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Telegram\Bot\Commands\Command;

class ValidateOrderCommand extends Command
{
    protected string $name = "validate";
    protected string $description = "Validate order data";

    public function handle(): void
    {
        $arguments = $this->getUpdate()->getMessage()->getText(true);

        Artisan::call('validate:order-data', ['orderID' => $arguments]);
        $output = Artisan::output();
        $this->replyWithMessage(['text' => $output]);

        $validationResults = DB::table('errors')->where('order_id', $arguments)->first();
        $message = $validationResults ? $this->formatValidationResults($validationResults) : "No errors found for Order ID: {$arguments}";

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
