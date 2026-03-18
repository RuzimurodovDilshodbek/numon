<?php

namespace App\Filament\Resources\LessonResource\Pages;

use App\Filament\Resources\LessonResource;
use App\Filament\Widgets\LessonStudentsWidget;
use App\Models\Task;
use Carbon\Carbon;
use Filament\Actions;
use Filament\Forms;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewLesson extends ViewRecord
{
    protected static string $resource = LessonResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),

            Actions\Action::make('add_task')
                ->label('Vazifa qo\'shish')
                ->icon('heroicon-o-plus-circle')
                ->color('success')
                ->form([
                    Forms\Components\TextInput::make('title')
                        ->label('Sarlavha')
                        ->required(),
                    Forms\Components\RichEditor::make('description')
                        ->label('Tavsif')
                        ->toolbarButtons(['bold', 'italic', 'underline', 'bulletList', 'orderedList', 'link'])
                        ->columnSpanFull(),
                    Forms\Components\FileUpload::make('attachment_path')
                        ->label('Rasm / fayl')
                        ->image()
                        ->disk('public')
                        ->directory('task-attachments')
                        ->columnSpanFull(),
                    Forms\Components\Select::make('type')
                        ->label('Turi')
                        ->options([
                            'in_lesson'          => "Dars ichida so'raladigan",
                            'extra_text'         => 'Extra matn (rasm)',
                            'video_conversation' => 'Video suhbat',
                            'vocabulary'         => "Lug'at imtihoni",
                        ])
                        ->required()
                        ->live(),
                    Forms\Components\DateTimePicker::make('due_date')
                        ->label('Topshirish muddati')
                        ->visible(fn (Forms\Get $get) => in_array($get('type'), ['extra_text', 'video_conversation', 'vocabulary'])),
                    Forms\Components\TextInput::make('max_score')
                        ->label('Maksimal ball')
                        ->numeric(),
                ])
                ->action(function (array $data) {
                    $task = Task::create([
                        'group_id'        => $this->record->group_id,
                        'lesson_id'       => $this->record->id,
                        'title'           => $data['title'],
                        'description'     => $data['description'] ?? null,
                        'attachment_path' => $data['attachment_path'] ?? null,
                        'type'            => $data['type'],
                        'due_date'        => $data['due_date'] ?? null,
                        'max_score'       => $data['max_score'] ?? null,
                        'is_active'       => true,
                        'created_by'      => auth()->id(),
                    ]);
                    Notification::make()->title("Vazifa qo'shildi: {$task->title}")->success()->send();
                }),

            Actions\Action::make('generate_secret_code')
                ->label('Maxfiy kod')
                ->icon('heroicon-o-key')
                ->color('warning')
                ->visible(fn () => in_array($this->record->status, ['scheduled', 'ongoing']))
                ->action(function () {
                    $code = strtoupper(\Illuminate\Support\Str::random(6));
                    $this->record->update([
                        'secret_code'            => $code,
                        'secret_code_expires_at' => now()->addMinutes(30),
                        'status'                 => 'ongoing',
                    ]);
                    $this->record->group->activeStudents->each(function ($student) use ($code) {
                        if ($student->telegram_id) {
                            dispatch(new \App\Jobs\SendSecretCodeNotificationJob($student, $this->record, $code));
                        }
                    });
                    Notification::make()
                        ->title("Maxfiy kod: {$code}")
                        ->body('30 daqiqa amal qiladi.')
                        ->success()
                        ->send();
                }),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make('Dars ma\'lumotlari')->schema([
                Infolists\Components\TextEntry::make('group.name')->label('Guruh'),
                Infolists\Components\TextEntry::make('lesson_date')
                    ->label('Sana')
                    ->date('d/m/Y'),
                Infolists\Components\TextEntry::make('start_time')
                    ->label('Vaqt')
                    ->formatStateUsing(fn ($state, $record) =>
                        Carbon::parse($state)->format('H:i') . ' — ' . Carbon::parse($record->end_time)->format('H:i')
                    ),
                Infolists\Components\TextEntry::make('status')
                    ->label('Holat')
                    ->badge()
                    ->color(fn ($state) => match($state) {
                        'scheduled' => 'gray', 'ongoing' => 'warning',
                        'completed' => 'success', 'cancelled' => 'danger', default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => match($state) {
                        'scheduled' => 'Rejalashtirilgan', 'ongoing' => 'Davom etmoqda',
                        'completed' => 'Tugagan', 'cancelled' => 'Bekor qilingan', default => $state,
                    }),
                Infolists\Components\TextEntry::make('title')->label('Mavzu')->placeholder('—'),
                Infolists\Components\TextEntry::make('zoom_link')->label('Zoom link')->placeholder('—'),
                Infolists\Components\TextEntry::make('secret_code')
                    ->label('Maxfiy kod')
                    ->placeholder('—')
                    ->copyable(),
            ])->columns(3),

            Infolists\Components\Section::make('Dars vazifalari')
                ->schema([
                    Infolists\Components\RepeatableEntry::make('tasks')
                        ->label('')
                        ->schema([
                            Infolists\Components\TextEntry::make('title')->label('Sarlavha'),
                            Infolists\Components\TextEntry::make('type')
                                ->label('Turi')->badge()
                                ->formatStateUsing(fn ($state) => match($state) {
                                    'in_lesson' => 'Dars ichida', 'extra_text' => 'Extra matn',
                                    'video_conversation' => 'Video', 'vocabulary' => "Lug'at", default => $state,
                                }),
                            Infolists\Components\TextEntry::make('due_date')
                                ->label('Muddat')->dateTime('d/m/Y H:i')->placeholder('—'),
                            Infolists\Components\TextEntry::make('submissions_count')
                                ->label('Topshirilgan')
                                ->getStateUsing(fn ($record) =>
                                    $record->submissions()->where('status', '!=', 'pending')->count() . ' ta'
                                ),
                        ])->columns(4),
                ])
                ->collapsible()
                ->collapsed(false),
        ]);
    }

    protected function getFooterWidgets(): array
    {
        return [LessonStudentsWidget::class];
    }

    protected function getWidgetData(): array
    {
        return ['lessonId' => $this->record->id];
    }
}
