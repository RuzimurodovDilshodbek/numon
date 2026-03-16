<?php

namespace App\Filament\Resources\VocabularyListResource\Pages;

use App\Filament\Resources\VocabularyListResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListVocabularyLists extends ListRecords
{
    protected static string $resource = VocabularyListResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
