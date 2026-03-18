<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Resources\Resource;
use App\Models\Lesson;
use App\Models\Group;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class LessonResource extends Resource
{
    protected static ?string $model = Lesson::class;
    protected static ?string $navigationLabel = 'Darslar';
    protected static ?string $navigationIcon = 'heroicon-o-calendar';
    protected static ?string $navigationGroup = 'Boshqaruv';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('group_id')
                ->label('Guruh')
                ->options(Group::where('is_active', true)->pluck('name', 'id'))
                ->required()
                ->searchable(),
            Forms\Components\TextInput::make('title')->label('Mavzu'),
            Forms\Components\Select::make('type')
                ->label('Dars turi')
                ->options(['theory' => 'Nazariy', 'practice' => 'Amaliy'])
                ->required(),
            Forms\Components\DatePicker::make('lesson_date')->label('Sana')->required(),
            Forms\Components\TimePicker::make('start_time')->label('Boshlanish')->seconds(false)->required(),
            Forms\Components\TimePicker::make('end_time')->label('Tugash')->seconds(false)->required(),
            Forms\Components\TextInput::make('zoom_link')->label('Zoom link')->url(),
            Forms\Components\Select::make('status')
                ->label('Holat')
                ->options([
                    'scheduled' => 'Rejalashtirilgan',
                    'ongoing' => 'Davom etmoqda',
                    'completed' => 'Tugagan',
                    'cancelled' => 'Bekor qilingan',
                ]),
            Forms\Components\Textarea::make('notes')->label('Izohlar'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('lesson_date', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('group.name')->label('Guruh')->searchable(),
                Tables\Columns\TextColumn::make('lesson_date')->label('Sana')->date('d.m.Y')->sortable(),
                Tables\Columns\TextColumn::make('start_time')->label('Boshlanish'),
                Tables\Columns\TextColumn::make('type')
                    ->label('Turi')
                    ->badge()
                    ->color(fn ($state) => match($state) {
                        'theory' => 'info',
                        'practice' => 'success',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('status')
                    ->label('Holat')
                    ->badge()
                    ->color(fn ($state) => match($state) {
                        'scheduled' => 'gray',
                        'ongoing' => 'warning',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\IconColumn::make('secret_code')
                    ->label('Kod')
                    ->boolean()
                    ->trueIcon('heroicon-o-lock-closed'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('group_id')
                    ->label('Guruh')
                    ->options(Group::pluck('name', 'id')),
                Tables\Filters\SelectFilter::make('status')
                    ->label('Holat')
                    ->options([
                        'scheduled' => 'Rejalashtirilgan',
                        'ongoing' => 'Davom etmoqda',
                        'completed' => 'Tugagan',
                        'cancelled' => 'Bekor qilingan',
                    ]),
                Tables\Filters\Filter::make('today')
                    ->label('Bugungi')
                    ->query(fn ($query) => $query->whereDate('lesson_date', today())),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('generate_secret_code')
                    ->label('Maxfiy kod yaratish')
                    ->icon('heroicon-o-key')
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
                            ->body("30 daqiqa amal qiladi. O'quvchilarga yuborildi.")
                            ->success()
                            ->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Resources\LessonResource\Pages\ListLessons::route('/'),
            'create' => \App\Filament\Resources\LessonResource\Pages\CreateLesson::route('/create'),
            'edit' => \App\Filament\Resources\LessonResource\Pages\EditLesson::route('/{record}/edit'),
            'view' => \App\Filament\Resources\LessonResource\Pages\ViewLesson::route('/{record}'),
        ];
    }
}
