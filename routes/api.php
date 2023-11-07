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

Route::get('/bot/getwebhookinfo', function () {
    return Telegram::getWebhookInfo();
});

Route::get('/bot/setwebhook', function () {
    $url = 'https://dev41.devzone.bio/api/bot/3f0d8abc838a4d9184f3b1b5badf00e2';
    return Telegram::setWebhook(['url' => $url]);
});
