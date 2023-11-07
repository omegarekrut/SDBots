<?php

namespace App\Telegram\Commands;

use Telegram\Bot\Actions;
use Telegram\Bot\Commands\Command;
use Telegram\Bot\Keyboard\Keyboard;

class StartCommand extends Command
{
    protected string $name = 'start';
    protected array $aliases = ['subscribe'];
    protected string $description = 'Start Command to get you started';

    public function handle()
    {
        $this->replyWithChatAction(['action' => Actions::TYPING]);

        $keyboard = Keyboard::make()
            ->inline()
            ->row(
                Keyboard::inlineButton(['text' => 'Button 1', 'callback_data' => 'button1']),
                Keyboard::inlineButton(['text' => 'Button 2', 'callback_data' => 'button2'])
            );

        $this->replyWithMessage([
            'text' => 'Hey there! Welcome to our bot! Choose an option:',
            'reply_markup' => $keyboard
        ]);
    }
}
