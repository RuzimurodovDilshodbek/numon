<?php

namespace App\Telegram\Handlers;

use SergiX44\Nutgram\Nutgram;
use App\Models\User;
use App\Models\Task;
use App\Models\TaskSubmission;
use App\Models\VocabularyTask;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class TaskSubmissionHandler
{
    public function handlePhoto(Nutgram $bot): void
    {
        $student = $this->getStudent($bot);
        if (!$student) return;

        // Find pending extra_text task with photo requirement
        $submission = TaskSubmission::where('student_id', $student->id)
            ->where('status', 'pending')
            ->whereHas('task', fn($q) => $q->where('type', 'extra_text'))
            ->with('task')
            ->first();

        if (!$submission) {
            $bot->sendMessage("📸 Rasm qabul qilindi, lekin aktiv vazifa topilmadi.");
            return;
        }

        $photos = $bot->message()->photo;
        $photo = end($photos);
        $fileId = $photo->file_id;

        $file = $bot->getFile($fileId);
        $url = "https://api.telegram.org/file/bot" . config('nutgram.token') . "/{$file->file_path}";
        $content = @file_get_contents($url);
        $filename = 'task_submissions/' . $student->id . '_' . time() . '.jpg';

        if ($content) {
            Storage::put($filename, $content);
        }

        $submission->update([
            'status'      => 'submitted',
            'file_path'   => $filename,
            'submitted_at' => now(),
        ]);

        $bot->sendMessage("✅ Vazifa topshirildi! *{$submission->task->title}*\n\nO'qituvchi tekshirgandan so'ng ball qo'yiladi.", parse_mode: 'Markdown');
    }

    public function handleVideo(Nutgram $bot): void
    {
        $student = $this->getStudent($bot);
        if (!$student) return;

        $submission = TaskSubmission::where('student_id', $student->id)
            ->where('status', 'pending')
            ->whereHas('task', fn($q) => $q->where('type', 'video_conversation'))
            ->with('task')
            ->first();

        if (!$submission) {
            $bot->sendMessage("🎬 Video qabul qilindi, lekin aktiv vazifa topilmadi.");
            return;
        }

        $video = $bot->message()->video;
        $fileId = $video->file_id;

        $file = $bot->getFile($fileId);
        $url = "https://api.telegram.org/file/bot" . config('nutgram.token') . "/{$file->file_path}";
        $content = @file_get_contents($url);
        $filename = 'task_submissions/' . $student->id . '_' . time() . '.mp4';

        if ($content) {
            Storage::put($filename, $content);
        }

        $submission->update([
            'status'       => 'submitted',
            'file_path'    => $filename,
            'submitted_at' => now(),
        ]);

        $bot->sendMessage("✅ Video vazifa topshirildi! *{$submission->task->title}*\n\nO'qituvchi tekshirgandan so'ng ball qo'yiladi.", parse_mode: 'Markdown');
    }

    public function openVocabExam(Nutgram $bot): void
    {
        $student = $this->getStudent($bot);
        if (!$student) return;

        $callbackData = $bot->callbackQuery()?->data ?? '';
        if (!preg_match('/vocab_exam_(\d+)/', $callbackData, $m)) {
            $bot->answerCallbackQuery(text: 'Xato so\'rov');
            return;
        }

        $taskId = (int) $m[1];
        $vocabTask = VocabularyTask::where('task_id', $taskId)
            ->with('vocabularyList.words')
            ->first();

        if (!$vocabTask) {
            $bot->answerCallbackQuery(text: 'Lug\'at topilmadi');
            return;
        }

        $words = $vocabTask->vocabularyList->words ?? collect();
        if ($words->isEmpty()) {
            $bot->answerCallbackQuery(text: 'So\'zlar topilmadi');
            return;
        }

        // Cache-based token (30 daqiqa muddatli)
        $token = Str::random(32);
        Cache::put("vocab_exam_{$token}", [
            'task_id'    => $taskId,
            'student_id' => $student->id,
        ], now()->addMinutes(30));

        $webAppUrl = config('app.url') . "/vocab-exam/{$token}";

        $bot->answerCallbackQuery();
        $bot->sendMessage(
            "📝 *Lug'at imtihoni*\n\n" .
            "📚 {$vocabTask->vocabularyList->title}\n" .
            "⏱ Vaqt: " . ($vocabTask->time_limit_minutes ?? '—') . " daqiqa\n" .
            "✅ O'tish: {$vocabTask->pass_percent}%\n\n" .
            "Imtihonni boshlash uchun quyidagi havolaga o'ting:",
            parse_mode: 'Markdown'
        );
        $bot->sendMessage("🔗 " . $webAppUrl);
    }

    private function getStudent(Nutgram $bot): ?User
    {
        $student = User::where('telegram_id', (string) $bot->userId())->first();

        if (!$student) {
            $bot->sendMessage("⚠️ Siz ro'yxatdan o'tmagansiz. /start bosing.");
            return null;
        }

        return $student;
    }
}
