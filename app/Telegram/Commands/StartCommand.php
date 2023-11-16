<?php

namespace App\Telegram\Commands;

use Illuminate\Support\Facades\Log;
use Telegram\Bot\Commands\Command;

class StartCommand extends Command
{
    protected string $name = 'start';
    protected array $aliases = ['subscribe'];
    protected string $description = 'Start Command to get you started';

    public function handle(): void
    {
        $update = $this->getUpdate();
        $chat_id = $update->getMessage()->getChat()->getId();
        Log::info("Received /start from chat ID: " . $chat_id);

        $this->replyWithMessage([
            'text' => 'Hey, there! Welcome to our bot!',
        ]);
    }
}
