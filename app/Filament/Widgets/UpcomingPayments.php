<?php

namespace App\Filament\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use App\Models\Payment;

class UpcomingPayments extends BaseWidget
{
    protected static ?string $heading = "Joriy va kelgusi oy to'lovlari";
    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Payment::query()
                    ->whereIn('status', ['pending', 'overdue'])
                    ->whereBetween('period_month', [
                        now()->startOfMonth(),
                        now()->addMonth()->startOfMonth(),
                    ])
                    ->orderBy('period_month')
                    ->orderByRaw("status = 'overdue' desc")
                    ->with(['student', 'group'])
            )
            ->columns([
                Tables\Columns\TextColumn::make('student.name')->label("O'quvchi"),
                Tables\Columns\TextColumn::make('group.name')->label('Guruh'),
                Tables\Columns\TextColumn::make('final_amount')->label('Summa')->money('UZS'),
                Tables\Columns\TextColumn::make('period_month')->label('Oy')->date('M Y'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Holat')
                    ->badge()
                    ->color(fn ($state) => match($state) {
                        'pending' => 'warning',
                        'overdue' => 'danger',
                        'paid' => 'success',
                        default => 'gray',
                    }),
            ]);
    }
}
