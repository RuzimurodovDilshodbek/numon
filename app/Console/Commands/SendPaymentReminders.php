<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Payment;
use App\Models\NotificationLog;
use App\Jobs\SendTelegramMessageJob;
use Carbon\Carbon;

class SendPaymentReminders extends Command
{
    protected $signature = 'payments:send-reminders';
    protected $description = "To'lov muddati yaqinlashayotganlarga eslatma yuboradi";

    public function handle(): void
    {
        // 3 kun va 1 kun qolganda eslatma
        $daysAhead = [3, 1];

        foreach ($daysAhead as $days) {
            $targetDate = today()->addDays($days);

            $payments = Payment::where('status', 'pending')
                ->where('period_month', $targetDate->format('Y-m-01'))
                ->with(['student', 'group'])
                ->get();

            foreach ($payments as $payment) {
                $student = $payment->student;
                if (!$student?->telegram_id) continue;

                // Duplicate tekshirish
                $alreadySent = NotificationLog::where('type', 'payment_reminder')
                    ->where('user_id', $student->id)
                    ->where('metadata->payment_id', $payment->id)
                    ->where('metadata->days_ahead', $days)
                    ->exists();

                if ($alreadySent) continue;

                $amount = number_format($payment->final_amount, 0, '.', ' ');
                $month  = Carbon::parse($payment->period_month)->translatedFormat('F Y');

                $message = "💳 *To'lov eslatmasi*\n\n" .
                           "👤 {$student->name}\n" .
                           "👥 Guruh: {$payment->group->name}\n" .
                           "📅 Oy: {$month}\n" .
                           "💰 Summa: {$amount} so'm\n\n" .
                           ($days === 1 ? "⚠️ Ertaga muddat tugaydi!" : "⏰ {$days} kun qoldi!");

                dispatch(new SendTelegramMessageJob(
                    userId: $student->id,
                    telegramId: $student->telegram_id,
                    message: $message,
                    type: 'payment_reminder',
                    metadata: [
                        'payment_id' => $payment->id,
                        'days_ahead' => $days,
                    ]
                ));
            }
        }

        $this->info('Payment reminders sent at ' . now());
    }
}
