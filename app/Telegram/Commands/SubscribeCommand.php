<?php

namespace App\Telegram\Commands;

use App\Models\Subscription;
use Telegram\Bot\Commands\Command;

class SubscribeCommand extends Command
{
    public function handle(): void
    {
        $telegramUserId = $this->getUpdate()->getMessage()->getFrom()->getId();
        Subscription::updateOrCreate(
            ['telegram_user_id' => $telegramUserId],
            ['is_subscribed' => true]
        );

        $this->replyWithMessage(['text' => 'You have subscribed to hourly updates.']);
    }
}
