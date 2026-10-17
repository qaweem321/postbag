<?php

use App\Http\Controllers\Api\V1\CampaignController;
use App\Http\Controllers\Api\V1\ListController;
use App\Http\Controllers\Api\V1\SubscriberController;
use Illuminate\Support\Facades\Route;

// Token authentication (Sanctum). Tokens carry "read" and/or "write" abilities.
Route::prefix('v1')->middleware(['auth:sanctum', 'throttle:api'])->group(function () {
    Route::middleware('abilities:read')->group(function () {
        Route::get('lists', [ListController::class, 'index']);
        Route::get('lists/{list}', [ListController::class, 'show']);
        Route::get('lists/{list}/subscribers', [SubscriberController::class, 'index']);
        Route::get('lists/{list}/subscribers/{email}', [SubscriberController::class, 'show']);
        Route::get('campaigns', [CampaignController::class, 'index']);
        Route::get('campaigns/{campaign}', [CampaignController::class, 'show']);
    });

    Route::middleware('abilities:write')->group(function () {
        Route::post('lists/{list}/subscribers', [SubscriberController::class, 'store']);
        Route::delete('lists/{list}/subscribers/{email}', [SubscriberController::class, 'destroy']);
        Route::post('suppressions', [SubscriberController::class, 'suppress']);
    });
});
