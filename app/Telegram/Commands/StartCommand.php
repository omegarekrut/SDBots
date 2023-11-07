<?php

namespace App\Telegram\Commands;

use Telegram\Bot\Actions;
use Telegram\Bot\Commands\Command;
use Telegram\Bot\Keyboard\Keyboard;
use Telegram\Bot\Laravel\Facades\Telegram;

class StartCommand extends Command
{
    protected string $name = 'start';
    protected array $aliases = ['subscribe'];
    protected string $description = 'Start Command to get you started';

    public function handle(): void
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

    public function handleCallbackQuery($callbackQuery): void
    {
        $data = $callbackQuery->getData();

        switch ($data) {
            case 'button1':
                $responseText = 'You pressed button 1!';
                break;
            case 'button2':
                $responseText = 'You pressed button 2!';
                break;
            default:
                $responseText = 'Unknown operation';
                break;
        }

        Telegram::sendMessage([
            'chat_id' => $callbackQuery->getMessage()->getChat()->getId(),
            'text' => $responseText
        ]);

        Telegram::answerCallbackQuery([
            'callback_query_id' => $callbackQuery->getId()
        ]);
    }
}
