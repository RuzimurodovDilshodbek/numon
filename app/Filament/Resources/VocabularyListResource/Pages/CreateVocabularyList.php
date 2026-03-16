<?php

namespace App\Filament\Resources\VocabularyListResource\Pages;

use App\Filament\Resources\VocabularyListResource;
use App\Models\VocabularyWord;
use Filament\Resources\Pages\CreateRecord;
use League\Csv\Reader;
use Illuminate\Support\Facades\DB;

class CreateVocabularyList extends CreateRecord
{
    protected static string $resource = VocabularyListResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();
        return $data;
    }

    protected function afterCreate(): void
    {
        $data = $this->data;
        if (!empty($data['csv_file'])) {
            $path = storage_path('app/' . $data['csv_file']);
            if (file_exists($path)) {
                $csv = Reader::createFromPath($path, 'r');
                $csv->setHeaderOffset(0);

                DB::transaction(function () use ($csv) {
                    foreach ($csv->getRecords() as $i => $row) {
                        VocabularyWord::create([
                            'vocabulary_list_id' => $this->record->id,
                            'turkish_word' => trim($row['turkish_word'] ?? $row[0] ?? ''),
                            'uzbek_translation' => trim($row['uzbek_translation'] ?? $row[1] ?? ''),
                            'example_sentence' => trim($row['example_sentence'] ?? $row[2] ?? ''),
                            'difficulty_level' => (int) ($row['difficulty_level'] ?? $row[3] ?? 1),
                            'order_index' => $i,
                        ]);
                    }
                });
            }
        }
    }
}
