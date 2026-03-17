<?php

namespace App\Jobs;

use SergiX44\Nutgram\Nutgram;
use App\Models\NotificationLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendTelegramMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        public int     $userId,
        public string  $telegramId,
        public string  $message,
        public string  $type = 'general',
        public array   $metadata = [],
        public ?string $parseMode = 'Markdown',
    ) {}

    public function handle(Nutgram $bot): void
    {
        $params = [
            'text'       => $this->message,
            'chat_id'    => $this->telegramId,
            'parse_mode' => $this->parseMode,
        ];

        if (!empty($this->metadata['vocab_button']) && !empty($this->metadata['task_id'])) {
            $params['reply_markup'] = \SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup::make()
                ->addRow(
                    \SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton::make(
                        text: "📝 Imtihonni boshlash",
                        callback_data: "vocab_exam_{$this->metadata['task_id']}"
                    )
                );
        }

        $bot->sendMessage(...$params);

        NotificationLog::create([
            'user_id'  => $this->userId,
            'type'     => $this->type,
            'title'    => $this->type,
            'body'     => $this->message,
            'channel'  => 'telegram',
            'sent_at'  => now(),
            'metadata' => $this->metadata,
        ]);
    }
}
