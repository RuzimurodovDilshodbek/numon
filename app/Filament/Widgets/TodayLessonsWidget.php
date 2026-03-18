<?php

namespace App\Filament\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use App\Models\Lesson;
use Carbon\Carbon;
use Illuminate\Support\Str;

class TodayLessonsWidget extends BaseWidget
{
    protected static ?string $heading = '📅 Bugungi darslar';
    protected int | string | array $columnSpan = 'full';
    protected static ?int $sort = 2;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Lesson::whereDate('lesson_date', today())
                    ->with('group')
                    ->orderBy('start_time')
            )
            ->columns([
                Tables\Columns\TextColumn::make('start_time')
                    ->label('Vaqt')
                    ->formatStateUsing(fn ($state, $record) =>
                        Carbon::parse($state)->format('H:i') . ' — ' . Carbon::parse($record->end_time)->format('H:i')
                    ),
                Tables\Columns\TextColumn::make('group.name')
                    ->label('Guruh'),
                Tables\Columns\TextColumn::make('title')
                    ->label('Mavzu')
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Holat')
                    ->badge()
                    ->color(fn ($state) => match($state) {
                        'scheduled' => 'gray',
                        'ongoing'   => 'warning',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        default     => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => match($state) {
                        'scheduled' => 'Rejalashtirilgan',
                        'ongoing'   => 'Davom etmoqda',
                        'completed' => 'Tugagan',
                        'cancelled' => 'Bekor qilingan',
                        default     => $state,
                    }),
                Tables\Columns\TextColumn::make('attendances_count')
                    ->label('Davomat')
                    ->counts('attendances')
                    ->suffix(' ta'),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('Ochish')
                    ->icon('heroicon-o-eye')
                    ->url(fn (Lesson $record) => route('filament.admin.resources.lessons.view', $record)),
                Tables\Actions\Action::make('generate_secret_code')
                    ->label('Kod')
                    ->icon('heroicon-o-key')
                    ->color('warning')
                    ->visible(fn (Lesson $record) => in_array($record->status, ['scheduled', 'ongoing']))
                    ->action(function (Lesson $record) {
                        $code = strtoupper(Str::random(6));
                        $record->update([
                            'secret_code' => $code,
                            'secret_code_expires_at' => now()->addMinutes(30),
                            'status' => 'ongoing',
                        ]);
                        $record->group->activeStudents->each(function ($student) use ($record, $code) {
                            if ($student->telegram_id) {
                                dispatch(new \App\Jobs\SendSecretCodeNotificationJob($student, $record, $code));
                            }
                        });
                        \Filament\Notifications\Notification::make()
                            ->title("Maxfiy kod: {$code}")
                            ->body("30 daqiqa amal qiladi.")
                            ->success()
                            ->send();
                    }),
            ])
            ->emptyStateHeading('Bugun dars yo\'q')
            ->emptyStateIcon('heroicon-o-calendar');
    }
}
