<?php

namespace App\Filament\Resources\StudentResource\Pages;

use App\Filament\Resources\StudentResource;
use Filament\Actions;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewStudent extends ViewRecord
{
    protected static string $resource = StudentResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\EditAction::make()];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make("Asosiy ma'lumotlar")->schema([
                Infolists\Components\TextEntry::make('student_id')->label('Student ID')->copyable(),
                Infolists\Components\TextEntry::make('name')->label('Ism Familya'),
                Infolists\Components\TextEntry::make('phone')->label('Telefon'),
                Infolists\Components\TextEntry::make('email')->label('Email'),
                Infolists\Components\IconEntry::make('is_active')->label('Faol')->boolean(),
            ])->columns(2),

            Infolists\Components\Section::make('Telegram')->schema([
                Infolists\Components\TextEntry::make('telegram_id')->label('Telegram ID')->placeholder('Ulanmagan'),
                Infolists\Components\TextEntry::make('telegram_username')->label('Username')->placeholder('—'),
                Infolists\Components\ImageEntry::make('telegram_photo_url')->label('Profil rasmi')->height(80)->circular(),
            ])->columns(3),

            Infolists\Components\Section::make('Bot orqali to\'ldirilgan ma\'lumotlar')
                ->schema([
                    Infolists\Components\TextEntry::make('studentProfile.age')->label('Yosh')->placeholder('—'),
                    Infolists\Components\TextEntry::make('studentProfile.learning_goal')->label('O\'rganish maqsadi')->placeholder('—'),
                    Infolists\Components\TextEntry::make('studentProfile.previous_language_experience')->label('Oldingi tajriba')->placeholder('—'),
                    Infolists\Components\ImageEntry::make('studentProfile.photo_path')->label('Rasm')->height(120)->disk('local'),
                ])->columns(2),
        ]);
    }
}
