<?php

use Illuminate\Support\Facades\Route;
use SergiX44\Nutgram\Nutgram;

Route::post('/telegram/webhook', function (Nutgram $bot) {
    $bot->run();
})->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
