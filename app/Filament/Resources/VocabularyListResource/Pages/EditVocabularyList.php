<?php

namespace App\Filament\Resources\VocabularyListResource\Pages;

use App\Filament\Resources\VocabularyListResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditVocabularyList extends EditRecord
{
    protected static string $resource = VocabularyListResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
