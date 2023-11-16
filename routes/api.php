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

Route::get('/bot/setup-webhook', function () {
    return Telegram::setWebhook(['url' => "https://dev41.devzone.bio/api/bot/webhook"]);
});

Route::post('/bot/webhook', [\App\Http\Controllers\BotController::class, 'handleRequest']);

Route::get('/webhook/validate-order/{id}', [\App\Http\Controllers\WebhookController::class, 'validateOrder']);

