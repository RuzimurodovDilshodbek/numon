<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\VocabExamController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/vocab-exam/{token}', [VocabExamController::class, 'show'])->name('vocab-exam.show');
Route::post('/vocab-exam/{token}/submit', [VocabExamController::class, 'submit'])->name('vocab-exam.submit');
