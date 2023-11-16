<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Laravel\Facades\Telegram;

class OrderValidationService
{
    private TelegramValidationMessageService $telegramValidationMessageService;

    public function __construct(TelegramValidationMessageService $telegramValidationMessageService)
    {
        $this->telegramValidationMessageService = $telegramValidationMessageService;
    }
    /**
     * @throws Exception
     */
    public function validateOrder(array $data): array
    {
        $orderID = $data['order_id'];
        $carrierName = $data['carrier_name'] ?? 'Unknown Carrier';
        $chatIds = explode(',', str_replace(' ', '', $data['chat_id']));

        try {
            $exitCode = Artisan::call('validate:order-data', ['orderID' => $orderID]);
            $resultMessage = $exitCode === 0 ? 'Validation successful.' : 'Validation failed.';

            foreach ($chatIds as $chatId) {
                $formattedMessage = $this->telegramValidationMessageService->formatValidationResults(
                    $resultMessage,
                    $orderID,
                    $carrierName
                );
                $this->sendMessageToChat(trim($chatId), $formattedMessage);
            }

            return [
                'success' => $exitCode === 0,
                'message' => $resultMessage
            ];
        } catch (Exception $e) {
            Log::error('Error in order validation: ' . $e->getMessage());
            throw $e;
        }
    }

    private function sendMessageToChat(string $chatId, string $message): void
    {
        try {
            Telegram::sendMessage([
                'chat_id' => $chatId,
                'text' => $message
            ]);
        } catch (Exception $e) {
            Log::error("Failed to send message to Telegram chat (ID: $chatId): " . $e->getMessage());
        }
    }

    private function formatMessageForChat($message, $carrierName): string
    {
        return $message . "\nCarrier Name: " . $carrierName;
    }
}
