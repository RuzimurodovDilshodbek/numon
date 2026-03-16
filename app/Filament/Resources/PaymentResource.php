<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Resources\Resource;
use App\Models\Payment;
use App\Models\User;
use App\Models\Group;
use Filament\Forms\Form;
use Filament\Tables\Table;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;
    protected static ?string $navigationLabel = "To'lovlar";
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationGroup = 'Moliya';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('student_id')
                ->label("O'quvchi")
                ->options(User::role('student')->pluck('name', 'id'))
                ->required()
                ->searchable()
                ->live(),
            Forms\Components\Select::make('group_id')
                ->label('Guruh')
                ->options(fn (Forms\Get $get) =>
                    $get('student_id')
                        ? User::find($get('student_id'))?->activeGroups()->pluck('name', 'groups.id') ?? []
                        : Group::where('is_active', true)->pluck('name', 'id')
                )
                ->required()
                ->live(),
            Forms\Components\DatePicker::make('period_month')
                ->label("To'lov oyi")
                ->displayFormat('M Y')
                ->required(),
            Forms\Components\TextInput::make('amount')
                ->label("Asosiy summa (so'm)")
                ->numeric()
                ->required()
                ->live(debounce: 500),
            Forms\Components\TextInput::make('discount_percent')
                ->label('Skidka (%)')
                ->numeric()
                ->default(0)
                ->minValue(0)
                ->maxValue(100)
                ->live(debounce: 500),
            Forms\Components\TextInput::make('final_amount')
                ->label("To'lov miqdori")
                ->numeric()
                ->disabled()
                ->dehydrated()
                ->afterStateHydrated(function (Forms\Components\TextInput $component, Forms\Get $get) {
                    $amount = $get('amount') ?? 0;
                    $discount = $get('discount_percent') ?? 0;
                    $component->state(Payment::calculateFinal((float) $amount, (int) $discount));
                }),
            Forms\Components\Select::make('status')
                ->label('Holat')
                ->options([
                    'pending' => 'Kutilmoqda',
                    'paid' => "To'langan",
                    'overdue' => "Muddati o'tgan",
                    'cancelled' => 'Bekor qilingan',
                ])
                ->required()
                ->default('pending'),
            Forms\Components\DateTimePicker::make('paid_at')
                ->label("To'lov vaqti")
                ->visible(fn (Forms\Get $get) => $get('status') === 'paid'),
            Forms\Components\Select::make('payment_method')
                ->label("To'lov usuli")
                ->options(['cash' => 'Naqd', 'transfer' => "O'tkazma", 'other' => 'Boshqa'])
                ->visible(fn (Forms\Get $get) => $get('status') === 'paid'),
            Forms\Components\Textarea::make('note')->label('Izoh'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('student.name')->label("O'quvchi")->searchable(),
                Tables\Columns\TextColumn::make('group.name')->label('Guruh'),
                Tables\Columns\TextColumn::make('period_month')->label('Oy')->date('M Y'),
                Tables\Columns\TextColumn::make('final_amount')->label('Summa')->money('UZS'),
                Tables\Columns\TextColumn::make('discount_percent')->label('Skidka')->suffix('%'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Holat')
                    ->badge()
                    ->color(fn ($state) => match($state) {
                        'pending' => 'warning',
                        'paid' => 'success',
                        'overdue' => 'danger',
                        'cancelled' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => match($state) {
                        'pending' => 'Kutilmoqda',
                        'paid' => "To'langan",
                        'overdue' => "Muddati o'tgan",
                        'cancelled' => 'Bekor qilingan',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('paid_at')->label("To'langan")->dateTime('d.m.Y'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->label('Holat')->options([
                    'pending' => 'Kutilmoqda',
                    'paid' => "To'langan",
                    'overdue' => "Muddati o'tgan",
                ]),
                Tables\Filters\SelectFilter::make('group_id')->label('Guruh')
                    ->options(Group::pluck('name', 'id')),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('mark_paid')
                    ->label("To'langan deb belgilash")
                    ->icon('heroicon-o-check')
                    ->visible(fn (Payment $record) => $record->status === 'pending')
                    ->action(function (Payment $record) {
                        $record->update([
                            'status' => 'paid',
                            'paid_at' => now(),
                            'recorded_by' => auth()->id(),
                        ]);
                    })
                    ->requiresConfirmation(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Resources\PaymentResource\Pages\ListPayments::route('/'),
            'create' => \App\Filament\Resources\PaymentResource\Pages\CreatePayment::route('/create'),
            'edit' => \App\Filament\Resources\PaymentResource\Pages\EditPayment::route('/{record}/edit'),
        ];
    }
}
