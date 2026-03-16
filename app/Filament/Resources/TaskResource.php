<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Resources\Resource;
use App\Models\Task;
use App\Models\Group;
use App\Models\Lesson;
use App\Models\VocabularyList;
use Filament\Forms\Form;
use Filament\Tables\Table;

class TaskResource extends Resource
{
    protected static ?string $model = Task::class;
    protected static ?string $navigationLabel = 'Vazifalar';
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationGroup = 'Boshqaruv';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('group_id')
                ->label('Guruh')
                ->options(Group::where('is_active', true)->pluck('name', 'id'))
                ->required()
                ->live(),
            Forms\Components\Select::make('lesson_id')
                ->label('Dars (ixtiyoriy)')
                ->options(fn (Forms\Get $get) =>
                    $get('group_id')
                        ? Lesson::where('group_id', $get('group_id'))
                            ->orderBy('lesson_date', 'desc')
                            ->get()
                            ->pluck('lesson_date', 'id')
                        : []
                )
                ->nullable(),
            Forms\Components\TextInput::make('title')->label('Sarlavha')->required(),
            Forms\Components\Textarea::make('description')->label('Tavsif'),
            Forms\Components\Select::make('type')
                ->label('Vazifa turi')
                ->options([
                    'in_lesson' => "1. Dars ichida so'raladigan",
                    'extra_text' => '2. Extra matn (rasm)',
                    'video_conversation' => '3. Video suhbat',
                    'vocabulary' => "4. Lug'at imtihoni",
                ])
                ->required()
                ->live(),
            Forms\Components\DateTimePicker::make('due_date')
                ->label('Topshirish muddati')
                ->visible(fn (Forms\Get $get) => in_array($get('type'), ['extra_text', 'video_conversation', 'vocabulary'])),
            Forms\Components\TextInput::make('max_score')->label('Maksimal ball')->numeric(),

            Forms\Components\Section::make("Lug'at sozlamalari")
                ->schema([
                    Forms\Components\Select::make('vocabulary_task.vocabulary_list_id')
                        ->label("Lug'at ro'yxati")
                        ->options(VocabularyList::pluck('title', 'id'))
                        ->required(),
                    Forms\Components\TextInput::make('vocabulary_task.pass_percent')
                        ->label("O'tish foizi")
                        ->numeric()
                        ->default(80)
                        ->suffix('%'),
                    Forms\Components\TextInput::make('vocabulary_task.time_limit_minutes')
                        ->label('Vaqt chegarasi (daqiqa)')
                        ->numeric()
                        ->nullable(),
                    Forms\Components\Toggle::make('vocabulary_task.random_order')
                        ->label('Tasodifiy tartib')
                        ->default(true),
                ])
                ->visible(fn (Forms\Get $get) => $get('type') === 'vocabulary'),

            Forms\Components\Toggle::make('is_active')->label('Faol')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('group.name')->label('Guruh'),
                Tables\Columns\TextColumn::make('title')->label('Sarlavha')->searchable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('Turi')
                    ->badge()
                    ->color(fn ($state) => match($state) {
                        'in_lesson' => 'gray',
                        'extra_text' => 'info',
                        'video_conversation' => 'warning',
                        'vocabulary' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => match($state) {
                        'in_lesson' => 'Dars ichida',
                        'extra_text' => 'Extra matn',
                        'video_conversation' => 'Video',
                        'vocabulary' => "Lug'at",
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('due_date')->label('Muddat')->dateTime('d.m.Y H:i'),
                Tables\Columns\TextColumn::make('submissions_count')
                    ->label('Topshirilgan')
                    ->counts('submissions'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Resources\TaskResource\Pages\ListTasks::route('/'),
            'create' => \App\Filament\Resources\TaskResource\Pages\CreateTask::route('/create'),
            'edit' => \App\Filament\Resources\TaskResource\Pages\EditTask::route('/{record}/edit'),
            'view' => \App\Filament\Resources\TaskResource\Pages\ViewTask::route('/{record}'),
        ];
    }
}
