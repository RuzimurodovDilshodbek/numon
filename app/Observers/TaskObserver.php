<?php

namespace App\Observers;

use App\Models\Task;
use App\Models\TaskSubmission;
use App\Jobs\SendTelegramMessageJob;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup;

class TaskObserver
{
    public function created(Task $task): void
    {
        // in_lesson vazifalar bot orqali topshirilmaydi — skip
        if ($task->type === 'in_lesson') {
            return;
        }

        // Guruhning barcha faol o'quvchilariga submission yaratish + xabar
        $group = $task->group()->with('activeStudents')->first();
        if (!$group) return;

        foreach ($group->activeStudents as $student) {
            // Submission yaratish (duplicate bo'lmasligi uchun)
            $submission = TaskSubmission::firstOrCreate(
                [
                    'task_id'    => $task->id,
                    'student_id' => $student->id,
                ],
                [
                    'status' => 'pending',
                ]
            );

            if (!$student->telegram_id) continue;

            $message = $this->buildMessage($task);

            // Vocab uchun inline button, boshqalar uchun oddiy xabar
            if ($task->type === 'vocabulary') {
                dispatch(new SendTelegramMessageJob(
                    userId: $student->id,
                    telegramId: $student->telegram_id,
                    message: $message,
                    type: 'new_task',
                    metadata: [
                        'task_id'          => $task->id,
                        'task_submission_id' => $submission->id,
                        'vocab_button'     => true,
                    ]
                ));
            } else {
                dispatch(new SendTelegramMessageJob(
                    userId: $student->id,
                    telegramId: $student->telegram_id,
                    message: $message,
                    type: 'new_task',
                    metadata: [
                        'task_id'          => $task->id,
                        'task_submission_id' => $submission->id,
                    ]
                ));
            }
        }
    }

    private function buildMessage(Task $task): string
    {
        $typeLabel = match ($task->type) {
            'extra_text'         => "📸 Matn/Rasm yuborish",
            'video_conversation' => "🎬 Video yuborish",
            'vocabulary'         => "📝 Lug'at imtihoni",
            default              => $task->type,
        };

        $deadline = $task->due_date
            ? "\n⏰ Muddat: " . $task->due_date->format('d.m.Y H:i')
            : '';

        $instruction = match ($task->type) {
            'extra_text'         => "\n\nVazifani bajarish uchun rasm yoki matn yuboring.",
            'video_conversation' => "\n\nVazifani bajarish uchun video yuboring.",
            'vocabulary'         => "\n\nLug'at imtihonini boshlash uchun /tasks buyrug'ini bosing.",
            default              => '',
        };

        return "📋 *Yangi vazifa!*\n\n" .
               "🏷 {$task->title}\n" .
               "📂 Tur: {$typeLabel}" .
               $deadline .
               ($task->description ? "\n\n{$task->description}" : '') .
               $instruction;
    }
}
