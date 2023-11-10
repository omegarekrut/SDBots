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
        $arguments = $this->getUpdate()->getMessage()->getText(true);

        // Log the received arguments
        Log::info('ValidateOrderCommand started', ['orderID' => $arguments]);

        // Call the Artisan command
        $exitCode = Artisan::call('validate:order-data', ['orderID' => $arguments]);
        $output = Artisan::output();

        // Log the result of the Artisan command
        Log::info('Artisan command executed', [
            'exitCode' => $exitCode,
            'output' => $output
        ]);

        // Reply with the output of the Artisan command
        $this->replyWithMessage(['text' => $output]);

        // Fetch validation results from the database
        $validationResults = DB::table('errors')->where('order_id', $arguments)->first();
        $message = $validationResults ? $this->formatValidationResults($validationResults) : "No errors found for Order ID: {$arguments}";

        // Log the validation results
        Log::info('Validation results', [
            'orderID' => $arguments,
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
