<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Payment;

class MarkOverduePayments extends Command
{
    protected $signature = 'payments:mark-overdue';
    protected $description = "Muddati o'tgan to'lovlarni overdue deb belgilaydi";

    public function handle(): void
    {
        $count = Payment::where('status', 'pending')
            ->where('period_month', '<', today()->startOfMonth()->toDateString())
            ->update(['status' => 'overdue']);

        $this->info("{$count} ta to'lov overdue deb belgilandi.");
    }
}
