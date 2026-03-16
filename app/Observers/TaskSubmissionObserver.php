<?php

namespace App\Observers;

use App\Models\TaskSubmission;
use App\Jobs\SendTelegramMessageJob;

class TaskSubmissionObserver
{
    public function updated(TaskSubmission $submission): void
    {
        if (!$submission->wasChanged(['status', 'score', 'teacher_comment'])) {
            return;
        }

        if ($submission->status !== 'graded') {
            return;
        }

        $student = $submission->student;
        if (!$student?->telegram_id) return;

        $task    = $submission->task;
        $score   = $submission->score !== null
            ? "\n⭐ Ball: {$submission->score}" . ($task->max_score ? "/{$task->max_score}" : '')
            : '';
        $comment = $submission->teacher_comment
            ? "\n\n💬 Ustoz izohi:\n_{$submission->teacher_comment}_"
            : '';

        $message = "📝 *Vazifa baholandi!*\n\n" .
                   "📋 {$task->title}{$score}{$comment}";

        dispatch(new SendTelegramMessageJob(
            userId: $student->id,
            telegramId: $student->telegram_id,
            message: $message,
            type: 'task_graded',
            metadata: ['task_submission_id' => $submission->id]
        ));
    }
}
