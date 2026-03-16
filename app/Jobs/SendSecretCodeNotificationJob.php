<?php

namespace App\Jobs;

use App\Models\Lesson;
use App\Models\User;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Carbon\Carbon;

class SendSecretCodeNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 30;

    public function __construct(
        public User   $student,
        public Lesson $lesson,
        public string $code,
    ) {}

    public function handle(Nutgram $bot): void
    {
        if (empty($this->student->telegram_id)) {
            return;
        }

        $date = Carbon::parse($this->lesson->lesson_date)->format('d.m.Y');
        $time = substr($this->lesson->start_time, 0, 5);

        $keyboard = InlineKeyboardMarkup::make()
            ->addRow(
                InlineKeyboardButton::make(
                    text: '🔑 Kodni kiritish',
                    callback_data: "secret_code_prompt_{$this->lesson->id}"
                )
            );

        $bot->sendMessage(
            text: "🔔 *Dars boshlandi!*\n\n" .
                  "📅 {$date} | ⏰ {$time}\n" .
                  "👥 {$this->lesson->group->name}\n\n" .
                  "Davomatingizni belgilash uchun quyidagi tugmani bosing:",
            chat_id: $this->student->telegram_id,
            parse_mode: 'Markdown',
            reply_markup: $keyboard,
        );
    }
}
