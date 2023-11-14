<?php

namespace App\Console\Commands;

use App\Models\Error;
use App\Models\Subscription;
use App\Services\ErrorMessageService;
use Illuminate\Console\Command;
use Telegram\Bot\Laravel\Facades\Telegram;

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
                Telegram::sendMessage([
                    'chat_id' => $subscriber->telegram_user_id,
                    'text' => $message,
                    'parse_mode' => 'Markdown'
                ]);
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
        $formattedMessage = "🕒 Hourly Update:\n⚡️⚡️⚡️\n";
        $errorMessages = ErrorMessageService::getErrorMessages();

        foreach ($errors as $index => $error) {
            $formattedMessage .= $this->formatSingleError($error, $index, $errorMessages);
        }

        return $formattedMessage;
    }

    private function formatSingleError($error, int $index, array $errorMessages): string
    {
        $formattedError = "🔔 " . ($index + 1) . ". Order ID: {$error->order_id}:\n";
        foreach ($errorMessages as $key => $message) {
            if ($error->$key == 1) {
                $formattedError .= "- {$message}\n";
            }
        }
        $formattedError .= "\n";

        return $formattedError;
    }
}
