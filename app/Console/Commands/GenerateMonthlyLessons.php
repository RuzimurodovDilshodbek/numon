<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Group;
use App\Services\LessonGeneratorService;
use Carbon\Carbon;

class GenerateMonthlyLessons extends Command
{
    protected $signature = 'lessons:generate-monthly {--month= : YYYY-MM format}';
    protected $description = 'Barcha faol guruhlar uchun oylik darslarni generatsiya qiladi';

    public function handle(LessonGeneratorService $generator): void
    {
        $month = $this->option('month')
            ? Carbon::createFromFormat('Y-m', $this->option('month'))->startOfMonth()
            : now()->addMonth()->startOfMonth();

        $groups = Group::where('is_active', true)
            ->has('scheduleTemplates')
            ->get();

        if ($groups->isEmpty()) {
            $this->warn('Faol guruhlar topilmadi.');
            return;
        }

        $total = 0;
        foreach ($groups as $group) {
            $count = $generator->generateForMonth($group, $month);
            $total += $count;
            $this->info("✓ {$group->name}: {$count} dars yaratildi");
        }

        $this->info("\nJami: {$total} dars yaratildi ({$month->format('M Y')})");
    }
}
