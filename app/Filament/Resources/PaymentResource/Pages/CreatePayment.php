<?php

namespace App\Filament\Resources\PaymentResource\Pages;

use App\Filament\Resources\PaymentResource;
use App\Models\Payment;
use Filament\Resources\Pages\CreateRecord;

class CreatePayment extends CreateRecord
{
    protected static string $resource = PaymentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['recorded_by'] = auth()->id();
        $data['final_amount'] = Payment::calculateFinal(
            (float) ($data['amount'] ?? 0),
            (int) ($data['discount_percent'] ?? 0)
        );
        return $data;
    }
}
