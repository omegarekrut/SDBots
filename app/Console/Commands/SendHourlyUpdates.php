<?php

namespace App\Console\Commands;

use App\Models\Error;
use App\Models\Subscription;
use App\Services\ErrorMessageService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
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
                try {
                    Telegram::sendMessage([
                        'chat_id' => $subscriber->telegram_user_id,
                        'text' => $message,
                        'parse_mode' => 'MarkdownV2'
                    ]);
                } catch (\Telegram\Bot\Exceptions\TelegramResponseException $e) {
                    Log::error("Failed to send message to Telegram chat (ID: {$subscriber->telegram_user_id}): " . $e->getMessage());
                } catch (\Exception $e) {
                    Log::error("An error occurred when sending message to Telegram chat (ID: {$subscriber->telegram_user_id}): " . $e->getMessage());
                }
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
        $formattedMessage = "🕒 Hourly Update:\n\n⚡️⚡️⚡️\n\n";
        $errorMessages = ErrorMessageService::getErrorMessages();

        foreach ($errors as $index => $error) {
            $formattedMessage .= $this->formatSingleError($error, $index, $errorMessages);
        }

        return $this->escapeMarkdownV2Characters($formattedMessage);
    }

    private function formatSingleError($error, int $index, array $errorMessages): string
    {
        $formattedError = "🔔 " . ($index + 1) . ". Order ID: " . $this->escapeMarkdownV2Characters($error->order_id) . ":\n";
        foreach ($errorMessages as $key => $message) {
            if ($error->$key == 1) {
                $formattedError .= "- " . $this->escapeMarkdownV2Characters($message) . "\n";
            }
        }
        $formattedError .= "\n";

        return $formattedError;
    }

    private function escapeMarkdownV2Characters(string $text): string
    {
        $escapeChars = ['_', '[', ']', '~', '`', '>', '#', '+', '-', '=', '|', '{', '}', '.', '!'];

        foreach ($escapeChars as $char) {
            $text = str_replace($char, '\\' . $char, $text);
        }

        return $text;
    }
}
