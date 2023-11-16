<?php

namespace App\Services;

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
        $accessToken = $this->superDispatchService->getAccessToken();
        $orderID = $data['order_id'];
        $carrierName = $data['carrier_name'] ?? 'Unknown Carrier';
        $chatIds = explode(',', str_replace(' ', '', $data['chat_id']));

        $order = $this->superDispatchService->fetchOrder($orderID, $accessToken);

        try {
            $errorRecord = $this->orderValidationLogicService->validateOrder($order['data']);

            foreach ($chatIds as $chatId) {
                $formattedMessage = $this->telegramValidationMessageService->formatValidationResults(
                    $errorRecord->toArray(),
                    $orderID,
                    $carrierName
                );
                $this->sendMessageToChat(trim($chatId), $formattedMessage);
            }

            return [
                'success' => !$errorRecord->hasErrors(),
                'message' => 'Validation processed.'
            ];
        } catch (Exception $e) {
            Log::error('Error in order validation: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Validates order with data retrieval.
     *
     * @param int $orderID
     * @param array $additionalData
     * @return array
     * @throws Exception
     */
    public function validateOrderWithRetrieval(int $orderID, array $additionalData): array
    {
        $accessToken = $this->superDispatchService->getAccessToken();

        if (!$accessToken) {
            throw new Exception('Failed to obtain access token.');
        }

        $order = $this->superDispatchService->fetchOrder($orderID, $accessToken);

        if (!$order || !isset($order['data'])) {
            throw new Exception('Failed to fetch order data or order data is incomplete.');
        }

        $orderData = array_merge($order['data'], $additionalData);

        return $this->validateOrder($orderData);
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
}
