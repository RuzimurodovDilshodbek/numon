<?php

namespace App\Filament\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use App\Models\User;
use App\Models\Lesson;
use App\Models\LessonAttendance;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class LessonStudentsWidget extends BaseWidget
{
    public ?int $lessonId = null;

    protected static ?string $heading = "O'quvchilar holati";
    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $lessonId = $this->lessonId;
        $lesson = Lesson::with('group')->find($lessonId);

        return $table
            ->query(
                User::role('student')
                    ->whereHas('groups', fn (Builder $q) =>
                        $q->where('group_id', $lesson?->group_id)
                          ->where('group_students.is_active', true)
                    )
                    ->with([
                        'attendances' => fn ($q) => $q->where('lesson_id', $lessonId),
                        'payments' => fn ($q) => $q->where('group_id', $lesson?->group_id)
                            ->where('period_month', Carbon::now()->startOfMonth()),
                    ])
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('O\'quvchi')
                    ->searchable(),

                Tables\Columns\TextColumn::make('attendance_status')
                    ->label('Davomat')
                    ->badge()
                    ->getStateUsing(function (User $record) use ($lessonId) {
                        $att = $record->attendances->first();
                        return $att?->status ?? 'not_marked';
                    })
                    ->color(fn ($state) => match($state) {
                        'present'    => 'success',
                        'late'       => 'warning',
                        'excused'    => 'info',
                        'absent'     => 'danger',
                        'not_marked' => 'gray',
                        default      => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => match($state) {
                        'present'    => '✅ Keldi',
                        'late'       => '⏰ Kechikdi',
                        'excused'    => '📋 Uzrli',
                        'absent'     => '❌ Kelmadi',
                        'not_marked' => '— Belgilanmagan',
                        default      => $state,
                    }),

                Tables\Columns\TextColumn::make('task_status')
                    ->label('Vazifalar')
                    ->getStateUsing(function (User $record) use ($lesson) {
                        if (!$lesson) return '—';
                        $taskIds = $lesson->tasks()->pluck('id');
                        if ($taskIds->isEmpty()) return '—';
                        $submitted = $record->taskSubmissions()
                            ->whereIn('task_id', $taskIds)
                            ->where('status', '!=', 'pending')
                            ->count();
                        $total = $taskIds->count();
                        return "{$submitted}/{$total}";
                    })
                    ->badge()
                    ->color(fn ($state) => $state === '—' ? 'gray' : 'info'),

                Tables\Columns\TextColumn::make('payment_status')
                    ->label('To\'lov')
                    ->badge()
                    ->getStateUsing(fn (User $record) => $record->payments->first()?->status ?? 'no_data')
                    ->color(fn ($state) => match($state) {
                        'paid'     => 'success',
                        'pending'  => 'warning',
                        'overdue'  => 'danger',
                        'no_data'  => 'gray',
                        default    => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => match($state) {
                        'paid'    => "✅ To'langan",
                        'pending' => '⏳ Kutilmoqda',
                        'overdue' => "❌ Muddati o'tgan",
                        'no_data' => '— Ma\'lumot yo\'q',
                        default   => $state,
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('mark_present')
                    ->label('Keldi')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->action(function (User $record) use ($lessonId) {
                        LessonAttendance::updateOrCreate(
                            ['lesson_id' => $lessonId, 'student_id' => $record->id],
                            ['status' => 'present', 'check_in_method' => 'manual', 'checked_in_at' => now()]
                        );
                    })
                    ->visible(fn (User $record) =>
                        ($record->attendances->first()?->status ?? 'not_marked') !== 'present'
                    ),

                Tables\Actions\Action::make('mark_absent')
                    ->label('Kelmadi')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->action(function (User $record) use ($lessonId) {
                        LessonAttendance::updateOrCreate(
                            ['lesson_id' => $lessonId, 'student_id' => $record->id],
                            ['status' => 'absent']
                        );
                    })
                    ->visible(fn (User $record) =>
                        ($record->attendances->first()?->status ?? 'not_marked') !== 'absent'
                    ),
            ])
            ->emptyStateHeading('O\'quvchilar yo\'q');
    }
}
