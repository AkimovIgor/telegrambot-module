<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::prefix('telegrambot')->group(function() {
    Route::get('/', 'TelegramBotController@index');

    Route::post('/settings', 'TelegramBotController@settings')
        ->name('telegrambot.settings');
    Route::post('/settings/set_webhook', 'TelegramBotController@setWebhook')
        ->name('telegrambot.set_webhook');

    Route::post('/settings/get_webhook_info', 'TelegramBotController@getWebhookinfo')
        ->name('telegrambot.get_webhook_info');

    Route::post('/webhook', 'TelegramBotController@webhook')
        ->name('telegrambot.webhook');
});
