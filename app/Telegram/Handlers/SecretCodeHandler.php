<?php

namespace App\Telegram\Handlers;

use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Conversations\Conversation;
use App\Models\User;
use App\Models\Lesson;
use App\Models\LessonAttendance;

class SecretCodeHandler extends Conversation
{
    protected ?string $step = 'ask_code';
    protected ?int $lessonId = null;

    public function ask_code(Nutgram $bot): void
    {
        // Extract lesson_id from callback data
        $callbackData = $bot->callbackQuery()?->data ?? '';
        if (preg_match('/secret_code_prompt_(\d+)/', $callbackData, $m)) {
            $this->lessonId = (int) $m[1];
        }

        $bot->sendMessage("🔑 Dars uchun maxfiy kodni kiriting:");
        $this->next('check_code');
    }

    public function check_code(Nutgram $bot): void
    {
        $input = trim($bot->message()->text ?? '');

        $lesson = Lesson::find($this->lessonId);
        if (!$lesson || !$lesson->secret_code) {
            $bot->sendMessage("❌ Dars topilmadi yoki kod mavjud emas.");
            $this->end();
            return;
        }

        if ($lesson->secret_code !== $input) {
            $bot->sendMessage("❌ Noto'g'ri kod. Qaytadan urinib ko'ring:");
            return;
        }

        $student = User::where('telegram_id', (string) $bot->userId())->first();
        if (!$student) {
            $bot->sendMessage("⚠️ Foydalanuvchi topilmadi.");
            $this->end();
            return;
        }

        $attendance = LessonAttendance::where('lesson_id', $lesson->id)
            ->where('student_id', $student->id)
            ->first();

        if (!$attendance) {
            LessonAttendance::create([
                'lesson_id'       => $lesson->id,
                'student_id'      => $student->id,
                'status'          => 'present',
                'check_in_method' => 'secret_code',
                'check_in_time'   => now(),
            ]);
        } else {
            $attendance->update([
                'status'          => 'present',
                'check_in_method' => 'secret_code',
                'check_in_time'   => now(),
            ]);
        }

        $bot->sendMessage("✅ Davomat qabul qilindi! Darsga xush kelibsiz. 🎓");
        $this->end();
    }

    public function prompt(Nutgram $bot): void
    {
        $this->ask_code($bot);
    }
}
