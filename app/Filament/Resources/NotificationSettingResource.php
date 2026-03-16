<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Resources\Resource;
use App\Models\NotificationSetting;
use Filament\Forms\Form;
use Filament\Tables\Table;

class NotificationSettingResource extends Resource
{
    protected static ?string $model = NotificationSetting::class;
    protected static ?string $navigationLabel = 'Bildirishnoma sozlamalari';
    protected static ?string $navigationIcon = 'heroicon-o-bell';
    protected static ?string $navigationGroup = 'Sozlamalar';

    public static function canAccess(): bool
    {
        return auth()->user()->isSuperAdmin();
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->label('Kalit nomi')
                ->helperText('e.g. lesson_reminder_15')
                ->required()
                ->unique(ignoreRecord: true),
            Forms\Components\TextInput::make('label')
                ->label("Ko'rsatiladigan nom")
                ->required(),
            Forms\Components\Textarea::make('description')->label('Tavsif'),
            Forms\Components\TextInput::make('minutes_before')
                ->label('Necha daqiqa oldin')
                ->numeric()
                ->required()
                ->helperText('0 = dars boshlanish vaqtida'),
            Forms\Components\Toggle::make('is_active')->label('Faol')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('label')->label('Nom'),
            Tables\Columns\TextColumn::make('minutes_before')->label('Daqiqa oldin')->suffix(' daqiqa'),
            Tables\Columns\ToggleColumn::make('is_active')->label('Faol'),
        ])
        ->actions([Tables\Actions\EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Resources\NotificationSettingResource\Pages\ListNotificationSettings::route('/'),
            'create' => \App\Filament\Resources\NotificationSettingResource\Pages\CreateNotificationSetting::route('/create'),
            'edit' => \App\Filament\Resources\NotificationSettingResource\Pages\EditNotificationSetting::route('/{record}/edit'),
        ];
    }
}
