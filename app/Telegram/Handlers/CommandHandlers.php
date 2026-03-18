<?php

namespace App\Telegram\Handlers;

use SergiX44\Nutgram\Nutgram;
use App\Models\User;
use App\Models\Lesson;
use App\Models\TaskSubmission;
use App\Models\Payment;
use Carbon\Carbon;

class CommandHandlers
{
    public function profile(Nutgram $bot): void
    {
        $student = $this->getStudent($bot);
        if (!$student) return;

        $groups = $student->activeGroups->pluck('name')->join(', ') ?: "Yo'q";
        $totalLessons = $student->attendances()->count();
        $presentLessons = $student->attendances()->where('status', 'present')->count();
        $attendanceRate = $totalLessons > 0 ? round($presentLessons / $totalLessons * 100) : 0;

        $bot->sendMessage(
            "👤 *Profilingiz*\n\n" .
            "📛 Ism: {$student->name}\n" .
            "🆔 ID: `{$student->student_id}`\n" .
            "📱 Guruhlar: {$groups}\n" .
            "📊 Davomat: {$attendanceRate}%\n" .
            "📅 Ro'yxatdan: " . $student->created_at->format('d.m.Y'),
            parse_mode: 'Markdown'
        );
    }

    public function lessons(Nutgram $bot): void
    {
        $student = $this->getStudent($bot);
        if (!$student) return;

        $lessons = Lesson::whereIn('group_id', $student->activeGroups->pluck('id'))
            ->where('lesson_date', '>=', today())
            ->where('status', 'scheduled')
            ->orderBy('lesson_date')
            ->orderBy('start_time')
            ->limit(7)
            ->with('group')
            ->get();

        if ($lessons->isEmpty()) {
            $bot->sendMessage("📅 Kelgusi 7 kunda dars yo'q.");
            return;
        }

        $text = "📅 *Kelgusi darslar:*\n\n";
        foreach ($lessons as $lesson) {
            $date = Carbon::parse($lesson->lesson_date)->format('d.m.Y');
            $text .= "▪️ {$date}\n";
            $start = Carbon::parse($lesson->start_time)->format('H:i');
            $end   = Carbon::parse($lesson->end_time)->format('H:i');
            $text .= "   🕐 {$start} — {$end}\n";
            $text .= "   👥 {$lesson->group->name}\n";
            if ($lesson->zoom_link) {
                $text .= "   🔗 [Zoom]({$lesson->zoom_link})\n";
            }
            $text .= "\n";
        }

        $bot->sendMessage($text, parse_mode: 'Markdown', disable_web_page_preview: true);
    }

    public function tasks(Nutgram $bot): void
    {
        $student = $this->getStudent($bot);
        if (!$student) return;

        $pendingSubmissions = TaskSubmission::where('student_id', $student->id)
            ->where('status', 'pending')
            ->with('task.group')
            ->get();

        if ($pendingSubmissions->isEmpty()) {
            $bot->sendMessage("✅ Barcha vazifalar topshirilgan!");
            return;
        }

        $text = "📋 *Topshirilmagan vazifalar:*\n\n";
        foreach ($pendingSubmissions as $submission) {
            $task = $submission->task;
            $deadline = $task->due_date
                ? "\n   ⏰ Muddat: " . Carbon::parse($task->due_date)->format('d.m.Y H:i')
                : '';
            $text .= "▪️ *{$task->title}*\n";
            $text .= "   👥 {$task->group->name}{$deadline}\n\n";
        }

        $bot->sendMessage($text, parse_mode: 'Markdown');
    }

    public function payments(Nutgram $bot): void
    {
        $student = $this->getStudent($bot);
        if (!$student) return;

        $payments = Payment::where('student_id', $student->id)
            ->orderBy('period_month', 'desc')
            ->limit(6)
            ->with('group')
            ->get();

        if ($payments->isEmpty()) {
            $bot->sendMessage("💳 To'lov ma'lumotlari yo'q.");
            return;
        }

        $text = "💳 *To'lov holati:*\n\n";
        foreach ($payments as $payment) {
            $status = match ($payment->status) {
                'paid' => "✅ To'langan",
                'pending' => '⏳ Kutilmoqda',
                'overdue' => "❌ Muddati o'tgan",
                'cancelled' => '⛔ Bekor qilingan',
                default => $payment->status,
            };
            $month = Carbon::parse($payment->period_month)->format('m.Y');
            $text .= "▪️ *{$month}* — {$payment->group->name}\n";
            $text .= "   💰 " . number_format($payment->final_amount, 0, '.', ' ') . " so'm\n";
            $text .= "   {$status}\n\n";
        }

        $bot->sendMessage($text, parse_mode: 'Markdown');
    }

    public function handleText(Nutgram $bot): void
    {
        $text = $bot->message()?->text ?? '';
        if (str_starts_with($text, '/')) return;

        $student = $this->getStudent($bot);
        if (!$student) return;

        match (true) {
            str_contains($text, 'Darslarim') => $this->lessons($bot),
            str_contains($text, 'Vazifalar') => $this->tasks($bot),
            str_contains($text, 'Profilim') => $this->profile($bot),
            str_contains($text, "To'lovlar") => $this->payments($bot),
            default => $bot->sendMessage("❓ Noma'lum buyruq. /start bosib ko'ring."),
        };
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
