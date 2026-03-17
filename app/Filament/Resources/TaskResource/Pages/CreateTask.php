<?php

namespace App\Filament\Resources\TaskResource\Pages;

use App\Filament\Resources\TaskResource;
use App\Models\VocabularyTask;
use Filament\Resources\Pages\CreateRecord;

class CreateTask extends CreateRecord
{
    protected static string $resource = TaskResource::class;

    protected ?array $vocabularyTaskData = null;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();

        if (!empty($data['vocabulary_task'])) {
            $this->vocabularyTaskData = $data['vocabulary_task'];
        }
        unset($data['vocabulary_task']);

        return $data;
    }

    protected function afterCreate(): void
    {
        if ($this->vocabularyTaskData && $this->record->type === 'vocabulary') {
            VocabularyTask::updateOrCreate(
                ['task_id' => $this->record->id],
                $this->vocabularyTaskData
            );
        }
    }
}
