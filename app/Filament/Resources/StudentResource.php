<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Resources\Resource;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Form;
use Filament\Tables\Table;

class StudentResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $navigationLabel = "O'quvchilar";
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'Boshqaruv';
    protected static ?string $slug = 'students';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->role('student');
    }

    public static function canCreate(): bool
    {
        return auth()->user()->can('manage_students');
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return auth()->user()->can('manage_students');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make("Asosiy ma'lumotlar")->schema([
                Forms\Components\TextInput::make('name')->label('Ism Familya')->required(),
                Forms\Components\TextInput::make('phone')->label('Telefon'),
                Forms\Components\TextInput::make('email')->label('Email')->email(),
                Forms\Components\Toggle::make('is_active')->label('Faol')->default(true),
            ])->columns(2),

            Forms\Components\Section::make("Tizim ma'lumotlari")->schema([
                Forms\Components\TextInput::make('student_id')
                    ->label('Student ID')
                    ->disabled()
                    ->helperText('Avtomatik beriladi'),
                Forms\Components\TextInput::make('telegram_id')
                    ->label('Telegram ID')
                    ->disabled()
                    ->helperText("Bot orqali ulanganida to'ldiriladi"),
            ])->columns(2)->visibleOn('edit'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('student_id')
                    ->label('ID')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Ism Familya')
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone')->label('Telefon'),
                Tables\Columns\IconColumn::make('telegram_id')
                    ->label('Telegram')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle'),
                Tables\Columns\TextColumn::make('is_active')
                    ->label('Holat')
                    ->badge()
                    ->getStateUsing(fn ($record) => $record->is_active ? 'Faol' : 'Faol emas')
                    ->color(fn ($state) => $state === 'Faol' ? 'success' : 'danger'),
                Tables\Columns\TextColumn::make('activeGroups.name')
                    ->label('Guruhlar')
                    ->badge(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')->label('Faol holat'),
                Tables\Filters\TernaryFilter::make('telegram_id')
                    ->label('Telegram ulangan')
                    ->nullable(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Resources\StudentResource\Pages\ListStudents::route('/'),
            'create' => \App\Filament\Resources\StudentResource\Pages\CreateStudent::route('/create'),
            'edit' => \App\Filament\Resources\StudentResource\Pages\EditStudent::route('/{record}/edit'),
            'view' => \App\Filament\Resources\StudentResource\Pages\ViewStudent::route('/{record}'),
        ];
    }
}
