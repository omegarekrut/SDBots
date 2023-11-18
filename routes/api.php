<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Telegram\Bot\Laravel\Facades\Telegram;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/bot/webhook-info', [\App\Http\Controllers\BotController::class, 'getWebhookInfo']);

Route::post('/bot/webhook', [\App\Http\Controllers\BotController::class, 'handleRequest']);

Route::post('/webhook/validate-order', [\App\Http\Controllers\WebhookController::class, 'validateOrder']);

