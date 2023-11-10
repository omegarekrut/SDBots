<?php

namespace App\Console\Commands;

use App\Models\Error;
use App\Models\Subscription;
use Illuminate\Console\Command;

class SendHourlyUpdates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-hourly-updates';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $subscribers = Subscription::where('is_subscribed', true)->get();

        foreach ($subscribers as $subscriber) {
            $errors = $this->fetchErrorsWithConditions();
            if ($errors->isNotEmpty()) {
                $message = $this->formatErrors($errors);
            }
        }
    }

    private function fetchErrorsWithConditions()
    {
        return Error::where(function ($query) {
            $errorConditions = [
                'err_loadid', 'err_client', 'err_amount', 'err_attach',
                'err_pickaddress', 'err_deladdress', 'err_email',
                'err_pickbol', 'err_method'
            ];

            foreach ($errorConditions as $condition) {
                $query->orWhere($condition, 1);
            }
        })->get();
    }

    private function formatErrors($errors): string
    {
        $formattedMessage = "";
        $errorMessages = $this->getErrorMessages();

        foreach ($errors as $index => $error) {
            $formattedMessage .= $this->formatSingleError($error, $index, $errorMessages);
        }

        return $formattedMessage;
    }

    private function getErrorMessages(): array
    {
        return [
            'err_loadid' => 'Empty or NULL Load ID',
            'err_client' => 'There is no client',
            'err_amount' => 'Price less than 100',
            'err_attach' => 'PDF BOL URL is missing or empty',
            'err_pickaddress' => 'Pickup address state or zip is missing',
            'err_deladdress' => 'Delivery address state or zip is missing',
            'err_email' => 'No email found in internal notes',
            'err_pickbol' => 'Less than 20 photos in vehicle data',
            'err_method' => 'Invalid payment method in vehicle data'
        ];
    }

    private function formatSingleError($error, int $index, array $errorMessages): string
    {
        $formattedError = ($index + 1) . ". Order ID: {$error->order_id}:\n";
        foreach ($errorMessages as $key => $message) {
            if ($error->$key == 1) {
                $formattedError .= "- {$message}\n";
            }
        }
        $formattedError .= "\n";

        return $formattedError;
    }
}
