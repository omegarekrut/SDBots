<?php

namespace App\Services;

use App\Models\Error;
use App\Models\Subscription;
use Exception;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Laravel\Facades\Telegram;

class OrderValidationService
{
    private const EXCLUDE_FLAG = "err_count";

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

        $chatIds = $this->parseChatIds($data['chat_id']);
        $this->saveChatIds($chatIds);

        $order = $this->superDispatchService->fetchOrder($orderID, $accessToken);
        $attachments = $this->superDispatchService->fetchAttachments($orderID, $accessToken);
        $orderNumber = $order['data']['number'] ?? 'Unknown';
        $carModelMake = $this->getCarModelMake($order);
        $errorRecord = $this->orderValidationLogicService->validateOrder($order['data'], $attachments['data']);
        $driverId = $order['data']['driver_id'] ?? 'Unknown';

        $this->logValidationResult($errorRecord);

        if ($this->hasValidationErrors($errorRecord)) {
            $formattedMessage = $this->telegramValidationMessageService->formatValidationResults(
                $errorRecord,
                $orderID,
                $carrierName,
                $orderNumber,
                $carModelMake,
                $driverId
            );

            $errorRecord->save();
            $this->sendMessagesToChats($chatIds, $formattedMessage);
        }

        return ['success' => '1', 'message' => 'Validation processed.'];
    }

    private function parseChatIds(string $chatIdString): array
    {
        return array_map('trim', explode(',', str_replace(' ', '', $chatIdString)));
    }

    private function saveChatIds(array $chatIds): void
    {
        foreach ($chatIds as $chatId) {
            Subscription::updateOrCreate(
                ['telegram_user_id' => $chatId],
                ['is_subscribed' => true]
            );
        }
    }

    private function getCarModelMake(array $order): string
    {
        return $order['data']['vehicles'][0]['make'] . ' ' . $order['data']['vehicles'][0]['model'];
    }

    private function logValidationResult(Error $errorRecord): void
    {
        Log::info('Order validation result', ['errorRecord' => $errorRecord->toArray()]);
    }

    private function hasValidationErrors(Error $errorRecord): bool
    {
        foreach ($errorRecord->getAttributes() as $key => $value) {
            if ($key !== self::EXCLUDE_FLAG && str_starts_with($key, 'err_') && $value == 1) {
                return true;
            }
        }
        return false;
    }

    private function sendMessagesToChats(array $chatIds, string $formattedMessage): void
    {
        foreach ($chatIds as $chatId) {
            $this->sendMessageToChat($chatId, $formattedMessage);
        }
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
