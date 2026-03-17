<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\VocabExamController;
use SergiX44\Nutgram\Nutgram;
use App\Telegram\TelegramBotKernel;

Route::get('/', function () {
    return view('welcome');
});

Route::post('/telegram/webhook', function (Nutgram $bot) {
    app(TelegramBotKernel::class)->register($bot);
    $bot->run();
})->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);

Route::get('/vocab-exam/{token}', [VocabExamController::class, 'show'])->name('vocab-exam.show');
Route::post('/vocab-exam/{token}/submit', [VocabExamController::class, 'submit'])->name('vocab-exam.submit');
