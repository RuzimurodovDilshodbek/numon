<?php

namespace App\Filament\Resources\TaskResource\Pages;

use App\Filament\Resources\TaskResource;
use App\Models\VocabularyTask;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTask extends EditRecord
{
    protected static string $resource = TaskResource::class;

    protected ?array $vocabularyTaskData = null;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $vocabTask = $this->record->vocabularyTask;
        if ($vocabTask) {
            $data['vocabulary_task'] = [
                'vocabulary_list_id'  => $vocabTask->vocabulary_list_id,
                'pass_percent'        => $vocabTask->pass_percent,
                'time_limit_minutes'  => $vocabTask->time_limit_minutes,
                'random_order'        => $vocabTask->random_order,
            ];
        }
        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (!empty($data['vocabulary_task'])) {
            $this->vocabularyTaskData = $data['vocabulary_task'];
        }
        unset($data['vocabulary_task']);
        return $data;
    }

    protected function afterSave(): void
    {
        if ($this->vocabularyTaskData && $this->record->type === 'vocabulary') {
            VocabularyTask::updateOrCreate(
                ['task_id' => $this->record->id],
                $this->vocabularyTaskData
            );
        }
    }
}
