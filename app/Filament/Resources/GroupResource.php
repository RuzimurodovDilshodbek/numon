<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Resources\Resource;
use App\Models\Group;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Services\LessonGeneratorService;

class GroupResource extends Resource
{
    protected static ?string $model = Group::class;
    protected static ?string $navigationLabel = 'Guruhlar';
    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationGroup = 'Boshqaruv';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make("Guruh ma'lumotlari")->schema([
                Forms\Components\TextInput::make('name')->label('Guruh nomi')->required(),
                Forms\Components\Textarea::make('description')->label('Tavsif'),
                Forms\Components\Select::make('level')
                    ->label('Daraja')
                    ->options([
                        'beginner' => "Boshlang'ich",
                        'intermediate' => "O'rta",
                        'advanced' => 'Yuqori',
                    ])
                    ->required(),
                Forms\Components\Select::make('lesson_type')
                    ->label('Dars turi')
                    ->options([
                        'theory' => 'Nazariy',
                        'practice' => 'Amaliy',
                        'mixed' => 'Aralash',
                    ])
                    ->required(),
                Forms\Components\TextInput::make('zoom_link')->label('Zoom link')->url(),
                Forms\Components\TextInput::make('monthly_fee')
                    ->label("Oylik to'lov (so'm)")
                    ->numeric()
                    ->required(),
                Forms\Components\TextInput::make('max_students')
                    ->label('Maksimal talabalar')
                    ->numeric()
                    ->default(15),
                Forms\Components\Toggle::make('is_active')->label('Faol')->default(true),
            ])->columns(2),

            Forms\Components\Section::make('Haftalik jadval')->schema([
                Forms\Components\Repeater::make('scheduleTemplates')
                    ->relationship()
                    ->label('Dars kunlari')
                    ->schema([
                        Forms\Components\Select::make('day_of_week')
                            ->label('Kun')
                            ->options([
                                0 => 'Dushanba',
                                1 => 'Seshanba',
                                2 => 'Chorshanba',
                                3 => 'Payshanba',
                                4 => 'Juma',
                                5 => 'Shanba',
                                6 => 'Yakshanba',
                            ])
                            ->required(),
                        Forms\Components\TimePicker::make('start_time')
                            ->label('Boshlanish')
                            ->seconds(false)
                            ->required(),
                        Forms\Components\TimePicker::make('end_time')
                            ->label('Tugash')
                            ->seconds(false)
                            ->required(),
                        Forms\Components\Toggle::make('is_active')->label('Faol')->default(true),
                    ])
                    ->columns(4)
                    ->addActionLabel("Kun qo'shish"),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Nomi')->searchable(),
                Tables\Columns\TextColumn::make('level')
                    ->label('Daraja')
                    ->badge()
                    ->color(fn ($state) => match($state) {
                        'beginner' => 'success',
                        'intermediate' => 'warning',
                        'advanced' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('monthly_fee')
                    ->label("Oylik to'lov")
                    ->money('UZS'),
                Tables\Columns\TextColumn::make('active_students_count')
                    ->label("O'quvchilar")
                    ->counts('activeStudents'),
                Tables\Columns\IconColumn::make('is_active')->label('Faol')->boolean(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('generate_lessons')
                    ->label('Darslar yaratish')
                    ->icon('heroicon-o-calendar')
                    ->action(function (Group $record) {
                        $count = app(LessonGeneratorService::class)->generateForNextMonth($record);
                        \Filament\Notifications\Notification::make()
                            ->title("Darslar muvaffaqiyatli yaratildi ({$count} ta)")
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            \App\Filament\Resources\GroupResource\RelationManagers\StudentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Resources\GroupResource\Pages\ListGroups::route('/'),
            'create' => \App\Filament\Resources\GroupResource\Pages\CreateGroup::route('/create'),
            'edit' => \App\Filament\Resources\GroupResource\Pages\EditGroup::route('/{record}/edit'),
            'view' => \App\Filament\Resources\GroupResource\Pages\ViewGroup::route('/{record}'),
        ];
    }
}
