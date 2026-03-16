<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\NotificationSetting;
use App\Models\Lesson;
use App\Models\NotificationLog;
use App\Jobs\SendTelegramMessageJob;
use Carbon\Carbon;

class CheckLessonReminders extends Command
{
    protected $signature = 'lessons:check-reminders';
    protected $description = 'Dars eslatmalarini tekshirib, keraklilarga bildirishnoma yuboradi';

    public function handle(): void
    {
        $settings = NotificationSetting::where('is_active', true)->get();

        foreach ($settings as $setting) {
            $targetTime = now()->addMinutes($setting->minutes_before);

            // Shu vaqtda boshlanadigan darslarni topish
            // 2.5 daqiqalik tolerance (scheduler har 5 daqiqada ishlaydi)
            $lessons = Lesson::where('status', 'scheduled')
                ->whereDate('lesson_date', $targetTime->toDateString())
                ->whereBetween(
                    DB::raw("CONCAT(lesson_date, ' ', start_time)::timestamp"),
                    [
                        $targetTime->copy()->subMinutes(2)->toDateTimeString(),
                        $targetTime->copy()->addMinutes(3)->toDateTimeString(),
                    ]
                )
                ->with(['group.activeStudents'])
                ->get();

            foreach ($lessons as $lesson) {
                $lesson->group->activeStudents->each(function ($student) use ($lesson, $setting) {
                    if (!$student->telegram_id) return;

                    // Duplicate tekshirish
                    $alreadySent = NotificationLog::where('type', 'lesson_reminder')
                        ->where('user_id', $student->id)
                        ->where('metadata->lesson_id', $lesson->id)
                        ->where('metadata->setting_id', $setting->id)
                        ->exists();

                    if ($alreadySent) return;

                    $message = $this->buildMessage($setting->minutes_before, $lesson, $student->name);

                    dispatch(new SendTelegramMessageJob(
                        userId: $student->id,
                        telegramId: $student->telegram_id,
                        message: $message,
                        type: 'lesson_reminder',
                        metadata: [
                            'lesson_id'  => $lesson->id,
                            'setting_id' => $setting->id,
                        ]
                    ));
                });
            }
        }

        $this->info('Reminder check completed at ' . now());
    }

    private function buildMessage(int $minutesBefore, Lesson $lesson, string $studentName): string
    {
        $date  = Carbon::parse($lesson->lesson_date)->format('d.m.Y');
        $time  = substr($lesson->start_time, 0, 5);
        $group = $lesson->group->name;

        if ($minutesBefore === 0) {
            return "🔔 *Dars boshlandi!*\n\n👤 {$studentName}\n📅 {$date} | ⏰ {$time}\n👥 Guruh: {$group}\n\nUstoz maxfiy kodni aytganda kiritishni unutmang! 🔐";
        }

        $timeText = $minutesBefore >= 60
            ? ($minutesBefore / 60) . " soatdan"
            : "{$minutesBefore} daqiqadan";

        return "⏰ *{$timeText} so'ng dars!*\n\n👤 {$studentName}\n📅 {$date} | 🕐 {$time}\n👥 Guruh: {$group}\n\nDarsga tayyorgarlik ko'ring! 📚";
    }
}
