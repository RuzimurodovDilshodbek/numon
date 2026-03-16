<?php

namespace App\Telegram\Handlers;

use SergiX44\Nutgram\Nutgram;
use App\Models\BotRegistration;
use App\Models\User;
use App\Telegram\Conversations\RegistrationConversation;

class StartHandler
{
    public function __invoke(Nutgram $bot): void
    {
        $telegramId = (string) $bot->userId();

        // Allaqachon ro'yxatdan o'tganmi?
        $existing = User::where('telegram_id', $telegramId)->first();
        if ($existing) {
            $bot->sendMessage(
                "Salom, {$existing->name}! 👋\n\nBuyruqlar:\n/profile — Profilim\n/lessons — Darslar\n/tasks — Vazifalar\n/payments — To'lovlar"
            );
            return;
        }

        BotRegistration::firstOrCreate(
            ['telegram_id' => $telegramId],
            ['step' => 'started']
        );

        RegistrationConversation::begin($bot);
    }
}
