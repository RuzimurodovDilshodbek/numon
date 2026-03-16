<?php

namespace App\Telegram;

use SergiX44\Nutgram\Nutgram;
use App\Telegram\Handlers\StartHandler;
use App\Telegram\Handlers\CommandHandlers;
use App\Telegram\Handlers\SecretCodeHandler;
use App\Telegram\Handlers\TaskSubmissionHandler;

class TelegramBotKernel
{
    public function register(Nutgram $bot): void
    {
        // /start — ro'yxatdan o'tish oqimi
        $bot->onCommand('start', StartHandler::class);

        // Tugallangan foydalanuvchilar uchun buyruqlar
        $bot->onCommand('profile', [CommandHandlers::class, 'profile']);
        $bot->onCommand('lessons', [CommandHandlers::class, 'lessons']);
        $bot->onCommand('tasks', [CommandHandlers::class, 'tasks']);
        $bot->onCommand('payments', [CommandHandlers::class, 'payments']);

        // Callback query lar (inline buttons)
        $bot->onCallbackQueryData('secret_code_prompt_{lesson_id}', [SecretCodeHandler::class, 'prompt']);
        $bot->onCallbackQueryData('vocab_exam_{task_id}', [TaskSubmissionHandler::class, 'openVocabExam']);

        // Rasm qabul (extra_text vazifa)
        $bot->onPhoto([TaskSubmissionHandler::class, 'handlePhoto']);

        // Video qabul (video_conversation vazifa)
        $bot->onVideo([TaskSubmissionHandler::class, 'handleVideo']);

        // Matn xabarlar
        $bot->onText('{text}', [CommandHandlers::class, 'handleText']);
    }
}
