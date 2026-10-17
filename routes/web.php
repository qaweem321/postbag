<?php

use App\Http\Controllers\Public\SubscriptionLinkController;
use App\Http\Controllers\Public\TrackingController;
use Illuminate\Support\Facades\Route;

// Links in emails. All signed: changing any part of the URL makes it invalid.
Route::middleware('signed')->group(function () {
    Route::get('/confirm/{subscription}', [SubscriptionLinkController::class, 'showConfirm'])->name('subscriptions.confirm');
    Route::post('/confirm/{subscription}', [SubscriptionLinkController::class, 'confirm']);
    Route::get('/unsubscribe/{subscription}', [SubscriptionLinkController::class, 'showUnsubscribe'])->name('subscriptions.unsubscribe');
    // Excluded from CSRF checks in bootstrap/app.php: mail providers POST here for one-click unsubscribe.
    Route::post('/unsubscribe/{subscription}', [SubscriptionLinkController::class, 'unsubscribe']);

    Route::get('/t/o/{send}', [TrackingController::class, 'open'])->name('track.open');
    Route::get('/t/c/{send}/{link}', [TrackingController::class, 'click'])->name('track.click');
});

require __DIR__.'/dashboard.php';
