<?php

namespace App\Telegram\Commands;

use App\Models\Subscription;
use Telegram\Bot\Commands\Command;

class UnsubscribeCommand extends Command
{
    public function handle(): void
    {
        $telegramUserId = $this->getUpdate()->getMessage()->getFrom()->getId();
        Subscription::where('telegram_user_id', $telegramUserId)
            ->update(['is_subscribed' => false]);

        $this->replyWithMessage(['text' => 'You have unsubscribed from hourly updates.']);
    }
}
