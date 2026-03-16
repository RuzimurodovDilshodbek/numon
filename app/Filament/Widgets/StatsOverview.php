<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\User;
use App\Models\Group;
use App\Models\Lesson;
use App\Models\Payment;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $activeStudents = User::role('student')->where('is_active', true)->count();
        $activeGroups = Group::where('is_active', true)->count();
        $todayLessons = Lesson::whereDate('lesson_date', today())
            ->whereIn('status', ['scheduled', 'ongoing'])->count();
        $overduePayments = Payment::where('status', 'overdue')->count();

        return [
            Stat::make("Faol o'quvchilar", $activeStudents)
                ->icon('heroicon-o-users')
                ->color('success'),
            Stat::make('Faol guruhlar', $activeGroups)
                ->icon('heroicon-o-user-group')
                ->color('info'),
            Stat::make('Bugungi darslar', $todayLessons)
                ->icon('heroicon-o-calendar')
                ->color('warning'),
            Stat::make("To'lov muddati o'tgan", $overduePayments)
                ->icon('heroicon-o-exclamation-circle')
                ->color('danger'),
        ];
    }
}
