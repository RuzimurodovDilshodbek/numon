<?php

namespace App\Filament\Resources\AdminResource\Pages;

use App\Filament\Resources\AdminResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAdmin extends CreateRecord
{
    protected static string $resource = AdminResource::class;

    protected function afterCreate(): void
    {
        $this->record->assignRole('admin');
        $permissions = $this->data['permissions'] ?? [];
        $this->record->syncPermissions($permissions);
    }
}
