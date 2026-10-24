<?php

use App\Http\Controllers\Api\BotAccountingController;
use App\Http\Controllers\Api\BotApiController;
use App\Http\Controllers\Api\FacebookInboxController;
use App\Http\Controllers\FacebookWebhookController;
use Illuminate\Support\Facades\Route;

// Credentials and scope are managed by each business in Settings > API & Automation.
Route::prefix('v1/bot')->middleware(['bot.token', 'throttle:bot-api'])->group(function () {
    Route::post('/facebook-events/claim', [FacebookInboxController::class, 'claim'])->middleware('bot.ability:automation:write');
    Route::post('/facebook-events/{id}/auto-reply', [FacebookInboxController::class, 'automatic'])->whereNumber('id')->middleware('bot.ability:facebook:send');
    Route::post('/facebook-events/{id}/draft', [FacebookInboxController::class, 'draft'])->whereNumber('id')->middleware('bot.ability:automation:write');
    Route::get('/health', [BotApiController::class, 'health']);
    Route::get('/products', [BotApiController::class, 'products'])->middleware('bot.ability:catalog:read');
    Route::get('/products/{id}', [BotApiController::class, 'product'])->whereNumber('id')->middleware('bot.ability:catalog:read');
    Route::get('/stock', [BotApiController::class, 'stock'])->middleware('bot.ability:catalog:read');
    Route::get('/reports/summary', [BotApiController::class, 'reportsSummary'])->middleware('bot.ability:reports:read');
    Route::get('/orders/{orderNumber}', [BotApiController::class, 'orderStatus'])->middleware('bot.ability:orders:read');
    Route::post('/orders', [BotApiController::class, 'storeOrder'])->middleware(['bot.ability:orders:write', 'bot.idempotency']);
    Route::get('/account-categories', [BotAccountingController::class, 'categories'])->middleware('bot.ability:transactions:write');
    Route::post('/transactions', [BotAccountingController::class, 'store'])->middleware(['bot.ability:transactions:write', 'bot.idempotency']);
    Route::get('/automation/config', [BotAccountingController::class, 'configuration'])->middleware('bot.ability:automation:read');
});

Route::get('/webhooks/facebook', [FacebookWebhookController::class, 'verify']);
Route::post('/webhooks/facebook', [FacebookWebhookController::class, 'receive']);
