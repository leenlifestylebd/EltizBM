<?php

use App\Http\Controllers\Api\BotApiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| SufiAI Bot API (v1)
|--------------------------------------------------------------------------
| Consumed by the n8n automation layer. Auth + business scoping via the
| bot.token middleware (static token in env BOT_API_TOKEN). ERP stays the
| single source of truth — prices/stock are always resolved server-side.
| All routes are prefixed with /api by the framework => /api/v1/bot/*
*/

Route::prefix('v1/bot')->middleware('bot.token')->group(function () {
    Route::get('/health', [BotApiController::class, 'health']);

    // Catalog (read)
    Route::get('/products', [BotApiController::class, 'products']);
    Route::get('/products/{id}', [BotApiController::class, 'product'])->whereNumber('id');
    Route::get('/stock', [BotApiController::class, 'stock']);

    // Reports (read)
    Route::get('/reports/summary', [BotApiController::class, 'reportsSummary']);

    // Orders
    Route::get('/orders/{orderNumber}', [BotApiController::class, 'orderStatus']);
    Route::post('/orders', [BotApiController::class, 'storeOrder']);
});
