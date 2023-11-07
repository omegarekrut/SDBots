<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;
use Illuminate\Http\Request;
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

    /**
     * Handle incoming webhook updates from Telegram.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function webhook(Request $request): JsonResponse
    {
        Telegram::commandsHandler(true);

        return response()->json(['status' => 'success']);
    }
}
