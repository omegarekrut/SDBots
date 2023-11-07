<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;
use Telegram\Bot\Laravel\Facades\Telegram;

class BotController extends Controller
{
    protected Api $telegram;

    /**
     * Create a new controller instance.
     *
     * @param  Api  $telegram
     */
    public function __construct(Api $telegram)
    {
        $this->telegram = $telegram;
    }

    /**
     * Show the bot information.
     * @throws TelegramSDKException
     */
    public function show(): \Telegram\Bot\Objects\User
    {
        return $this->telegram->getMe();
    }

    public function handleRequest(): string
    {
        $update = Telegram::commandsHandler(true);
        // Your code to handle the update here

        return 'ok';
    }
}
