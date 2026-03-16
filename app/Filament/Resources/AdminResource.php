<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Resources\Resource;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Form;
use Filament\Tables\Table;

class AdminResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $navigationLabel = 'Adminlar';
    protected static ?string $navigationIcon = 'heroicon-o-shield-check';
    protected static ?string $navigationGroup = 'Sozlamalar';
    protected static ?string $slug = 'admins';

    public static function canAccess(): bool
    {
        return auth()->user()->isSuperAdmin();
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->role('admin');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')->label('Ism')->required(),
            Forms\Components\TextInput::make('email')->label('Email')->email()->required(),
            Forms\Components\TextInput::make('password')
                ->label('Parol')
                ->password()
                ->required(fn ($operation) => $operation === 'create')
                ->dehydrateStateUsing(fn ($state) => filled($state) ? bcrypt($state) : null)
                ->dehydrated(fn ($state) => filled($state)),
            Forms\Components\CheckboxList::make('permissions')
                ->label('Ruxsatlar')
                ->options([
                    'manage_groups' => 'Guruhlarni boshqarish',
                    'manage_students' => "O'quvchilarni boshqarish",
                    'manage_payments' => "To'lovlarni boshqarish",
                    'manage_tasks' => 'Vazifalarni boshqarish',
                    'manage_attendance' => 'Davomatni boshqarish',
                    'view_reports' => "Hisobotlarni ko'rish",
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('name')->label('Ism'),
            Tables\Columns\TextColumn::make('email')->label('Email'),
            Tables\Columns\TextColumn::make('permissions')
                ->label('Ruxsatlar')
                ->getStateUsing(fn ($record) => $record->getAllPermissions()->pluck('name')->join(', ')),
        ])
        ->actions([Tables\Actions\EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Resources\AdminResource\Pages\ListAdmins::route('/'),
            'create' => \App\Filament\Resources\AdminResource\Pages\CreateAdmin::route('/create'),
            'edit' => \App\Filament\Resources\AdminResource\Pages\EditAdmin::route('/{record}/edit'),
        ];
    }
}
