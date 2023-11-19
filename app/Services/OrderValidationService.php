<?php

namespace App\Services;

use App\Models\Error;
use Exception;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Laravel\Facades\Telegram;

class OrderValidationService
{
    private OrderValidationLogicService $orderValidationLogicService;
    private TelegramValidationMessageService $telegramValidationMessageService;
    private SuperDispatchService $superDispatchService;

    public function __construct(
        OrderValidationLogicService $orderValidationLogicService,
        TelegramValidationMessageService $telegramValidationMessageService,
        SuperDispatchService $superDispatchService
    ) {
        $this->orderValidationLogicService = $orderValidationLogicService;
        $this->telegramValidationMessageService = $telegramValidationMessageService;
        $this->superDispatchService = $superDispatchService;
    }

    /**
     * Validates order data.
     *
     * @param array $data
     * @return array
     * @throws Exception
     */
    public function validateOrder(array $data): array
    {
        $accessToken = $this->superDispatchService->getAccessToken($data['carrier_name']);
        $orderID = $data['order_id'];
        $carrierName = $data['carrier_name'] ?? 'Unknown Carrier';
        $chatIds = explode(',', str_replace(' ', '', $data['chat_id']));

        $order = $this->superDispatchService->fetchOrder($orderID, $accessToken);
        $attachments = $this->superDispatchService->fetchAttachments($orderID, $accessToken);
        $orderNumber = $order['data']['number'] ?? 'Unknown';
        $carModelMake = $order['vehicles']['make'] . $order['vehicles']['model'];

        try {
            $errorRecord = $this->orderValidationLogicService->validateOrder($order['data'], $attachments['data']);

            Log::info('Order validation result', ['errorRecord' => $errorRecord->toArray()]);

            if ($this->hasValidationErrors($errorRecord)) {
                $formattedMessage = $this->telegramValidationMessageService->formatValidationResults(
                    $errorRecord,
                    $orderID,
                    $carrierName,
                    $orderNumber,
                    $carModelMake
                );

                foreach ($chatIds as $chatId) {
                    $this->sendMessageToChat(trim($chatId), $formattedMessage);
                }
            }

            return [
                'success' => '1',
                'message' => 'Validation processed.'
            ];
        } catch (Exception $e) {
            Log::error('Error in order validation: ' . $e->getMessage());
            throw $e;
        }
    }

    private function hasValidationErrors(Error $errorRecord): bool
    {
        foreach ($errorRecord->getAttributes() as $key => $value) {
            if (str_starts_with($key, 'err_') && $value == 1) {
                return true;
            }
        }
        return false;
    }

    private function sendMessageToChat(string $chatId, string $message): void
    {
        try {
            Telegram::sendMessage([
                'parse_mode' => 'MarkdownV2',
                'chat_id' => $chatId,
                'text' => $message,
            ]);
        } catch (Exception $e) {
            Log::error("Failed to send message to Telegram chat (ID: $chatId): " . $e->getMessage());
        }
    }
}
