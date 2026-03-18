<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\VocabExamController;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\RunningMode\Webhook;
use App\Telegram\TelegramBotKernel;

Route::get('/', function () {
    return view('welcome');
});

Route::post('/telegram/webhook', function (Nutgram $bot) {
    $bot->setRunningMode(Webhook::class);
    app(TelegramBotKernel::class)->register($bot);
    try {
        $bot->run();
    } catch (\Throwable) {
        // Telegram webhook always returns 200
    }
    return response('', 200);
})->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);

Route::get('/vocab-exam/{token}', [VocabExamController::class, 'show'])->name('vocab-exam.show');
Route::post('/vocab-exam/{token}/submit', [VocabExamController::class, 'submit'])->name('vocab-exam.submit');
