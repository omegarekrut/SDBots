<?php

namespace App\Console\Commands;

use App\Models\Error;
use App\Models\Subscription;
use App\Services\ErrorMessageService;
use App\Services\MarkdownFormatterService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Exceptions\TelegramResponseException;
use Telegram\Bot\Laravel\Facades\Telegram;

class SendHourlyUpdates extends Command
{
    protected $signature = 'app:send-hourly-updates';
    protected $description = 'Send hourly updates to subscribers';

    private ErrorMessageService $errorMessageService;
    private MarkdownFormatterService $markdownFormatter;

    public function __construct(ErrorMessageService $errorMessageService, MarkdownFormatterService $markdownFormatter)
    {
        parent::__construct();
        $this->errorMessageService = $errorMessageService;
        $this->markdownFormatter = $markdownFormatter;
    }

    public function handle(): void
    {
        $subscribers = Subscription::where('is_subscribed', true)->get();

        foreach ($subscribers as $subscriber) {
            $errors = $this->fetchErrorsWithConditions();
            if ($errors->isNotEmpty()) {
                $messages = $this->formatErrors($errors);
                $this->sendMessages($subscriber->telegram_user_id, $messages);
            }
        }
    }

    private function fetchErrorsWithConditions()
    {
        $errorConditions = [
            'err_loadid', 'err_client', 'err_amount', 'err_attach',
            'err_pickaddress', 'err_deladdress', 'err_email',
            'err_pickbol', 'err_method'
        ];

        return Error::where(function ($query) use ($errorConditions) {
            foreach ($errorConditions as $condition) {
                $query->orWhere($condition, 1);
            }
        })->get();
    }

    private function formatErrors($errors): array
    {
        return $errors->map(function ($error, $index) {
            return $this->formatSingleError($error, $index);
        })->toArray();
    }

    private function formatSingleError(Error $error, int $index): string
    {
        $formattedError = "🔔 Error " . ($index + 1) . " \(Order ID: " . $this->markdownFormatter->escape($error->order_id) . "\):\n";
        $errorMessages = $this->errorMessageService->getErrorMessages();

        foreach ($errorMessages as $key => $message) {
            if ($error->$key == 1) {
                $formattedError .= "- " . $this->markdownFormatter->escape($message) . "\n";
            }
        }

        return $formattedError;
    }

    private function sendMessages(int $chatId, array $messages): void
    {
        foreach ($messages as $message) {
            try {
                Telegram::sendMessage([
                    'chat_id' => $chatId,
                    'text' => $message,
                    'parse_mode' => 'MarkdownV2'
                ]);
            } catch (TelegramResponseException $e) {
                Log::error("Failed to send message to Telegram chat (ID: $chatId): " . $e->getMessage());
            } catch (\Exception $e) {
                Log::error("An error occurred when sending message to Telegram chat (ID: $chatId): " . $e->getMessage());
            }
        }
    }
}
