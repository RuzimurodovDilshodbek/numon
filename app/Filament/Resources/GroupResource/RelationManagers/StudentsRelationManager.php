<?php

namespace App\Filament\Resources\GroupResource\RelationManagers;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\RelationManagers\RelationManager;
use App\Models\Payment;
use App\Models\User;
use Carbon\Carbon;
use Filament\Notifications\Notification;

class StudentsRelationManager extends RelationManager
{
    protected static string $relationship = 'students';
    protected static ?string $title = "O'quvchilar";
    protected static ?string $label = "O'quvchi";

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\DatePicker::make('joined_at')
                ->label('Qo\'shilgan sana')
                ->default(today())
                ->required(),
            Forms\Components\TextInput::make('discount_percent')
                ->label('Chegirma (%)')
                ->numeric()
                ->minValue(0)
                ->maxValue(100)
                ->default(0),
            Forms\Components\Toggle::make('is_active')
                ->label('Faol')
                ->default(true),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Ism')
                    ->searchable(),
                Tables\Columns\TextColumn::make('student_id')
                    ->label('ID')
                    ->badge()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('phone')
                    ->label('Telefon'),
                Tables\Columns\TextColumn::make('pivot.joined_at')
                    ->label('Qo\'shilgan')
                    ->date('d.m.Y'),
                Tables\Columns\TextColumn::make('pivot.discount_percent')
                    ->label('Chegirma')
                    ->suffix('%')
                    ->badge()
                    ->color(fn ($state) => $state > 0 ? 'success' : 'gray'),
                Tables\Columns\IconColumn::make('pivot.is_active')
                    ->label('Faol')
                    ->boolean(),
            ])
            ->headerActions([
                Tables\Actions\Action::make('add_student')
                    ->label("O'quvchi qo'shish")
                    ->icon('heroicon-o-user-plus')
                    ->form([
                        Forms\Components\Select::make('student_id')
                            ->label("O'quvchi")
                            ->options(
                                User::role('student')
                                    ->whereDoesntHave('groups', fn ($q) =>
                                        $q->where('group_id', $this->getOwnerRecord()->id)
                                          ->where('group_students.is_active', true)
                                    )
                                    ->pluck('name', 'id')
                            )
                            ->searchable()
                            ->required(),

                        Forms\Components\DatePicker::make('joined_at')
                            ->label('Qo\'shilgan sana')
                            ->default(today())
                            ->required(),

                        Forms\Components\TextInput::make('discount_percent')
                            ->label('Chegirma (%)')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->default(0)
                            ->live(),

                        Forms\Components\Section::make("To'lov yaratish")->schema([
                            Forms\Components\Toggle::make('create_payment')
                                ->label('Shu oy uchun to\'lov yaratish')
                                ->default(true)
                                ->live(),

                            Forms\Components\DatePicker::make('period_month')
                                ->label('To\'lov oyi')
                                ->default(today()->startOfMonth())
                                ->displayFormat('m.Y')
                                ->visible(fn ($get) => $get('create_payment')),

                            Forms\Components\Select::make('payment_method')
                                ->label("To'lov usuli")
                                ->options([
                                    'cash'     => 'Naqd',
                                    'transfer' => "O'tkazma",
                                    'card'     => 'Karta',
                                ])
                                ->visible(fn ($get) => $get('create_payment')),

                            Forms\Components\Select::make('payment_status')
                                ->label('Holat')
                                ->options([
                                    'pending' => 'Kutilmoqda',
                                    'paid'    => "To'langan",
                                ])
                                ->default('pending')
                                ->visible(fn ($get) => $get('create_payment')),

                            Forms\Components\Textarea::make('payment_note')
                                ->label('Izoh')
                                ->rows(2)
                                ->visible(fn ($get) => $get('create_payment')),
                        ]),
                    ])
                    ->action(function (array $data) {
                        $group   = $this->getOwnerRecord();
                        $student = User::find($data['student_id']);

                        // Guruhga qo'shish
                        $group->students()->syncWithoutDetaching([
                            $student->id => [
                                'joined_at'        => $data['joined_at'],
                                'discount_percent' => $data['discount_percent'] ?? 0,
                                'is_active'        => true,
                            ],
                        ]);

                        // To'lov yaratish
                        if (!empty($data['create_payment'])) {
                            $discount    = (int) ($data['discount_percent'] ?? 0);
                            $amount      = (float) $group->monthly_fee;
                            $finalAmount = Payment::calculateFinal($amount, $discount);

                            Payment::create([
                                'student_id'      => $student->id,
                                'group_id'        => $group->id,
                                'amount'          => $amount,
                                'discount_percent'=> $discount,
                                'final_amount'    => $finalAmount,
                                'period_month'    => Carbon::parse($data['period_month'])->startOfMonth(),
                                'status'          => $data['payment_status'] ?? 'pending',
                                'payment_method'  => $data['payment_method'] ?? null,
                                'note'            => $data['payment_note'] ?? null,
                                'recorded_by'     => auth()->id(),
                                'paid_at'         => ($data['payment_status'] ?? '') === 'paid' ? now() : null,
                            ]);
                        }

                        Notification::make()
                            ->title("{$student->name} guruhga qo'shildi" . (!empty($data['create_payment']) ? " va to'lov yaratildi" : ''))
                            ->success()
                            ->send();
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('add_payment')
                    ->label("To'lov qo'sh")
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->form(function (User $record) {
                        $group = $this->getOwnerRecord();
                        $discount = (int) ($record->pivot->discount_percent ?? 0);
                        $amount   = (float) $group->monthly_fee;
                        return [
                            Forms\Components\DatePicker::make('period_month')
                                ->label('To\'lov oyi')
                                ->default(today()->startOfMonth())
                                ->displayFormat('m.Y')
                                ->required(),
                            Forms\Components\TextInput::make('discount_percent')
                                ->label('Chegirma (%)')
                                ->numeric()
                                ->default($discount)
                                ->minValue(0)->maxValue(100),
                            Forms\Components\Select::make('payment_method')
                                ->label("To'lov usuli")
                                ->options([
                                    'cash'     => 'Naqd',
                                    'transfer' => "O'tkazma",
                                    'card'     => 'Karta',
                                ]),
                            Forms\Components\Select::make('status')
                                ->label('Holat')
                                ->options([
                                    'pending' => 'Kutilmoqda',
                                    'paid'    => "To'langan",
                                ])
                                ->default('pending')
                                ->required(),
                            Forms\Components\Textarea::make('note')
                                ->label('Izoh')
                                ->rows(2),
                        ];
                    })
                    ->action(function (User $record, array $data) {
                        $group    = $this->getOwnerRecord();
                        $discount = (int) ($data['discount_percent'] ?? 0);
                        $amount   = (float) $group->monthly_fee;

                        Payment::updateOrCreate(
                            [
                                'student_id'   => $record->id,
                                'group_id'     => $group->id,
                                'period_month' => Carbon::parse($data['period_month'])->startOfMonth(),
                            ],
                            [
                                'amount'          => $amount,
                                'discount_percent'=> $discount,
                                'final_amount'    => Payment::calculateFinal($amount, $discount),
                                'status'          => $data['status'],
                                'payment_method'  => $data['payment_method'] ?? null,
                                'note'            => $data['note'] ?? null,
                                'recorded_by'     => auth()->id(),
                                'paid_at'         => $data['status'] === 'paid' ? now() : null,
                            ]
                        );

                        Notification::make()
                            ->title("To'lov saqlandi")
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('deactivate')
                    ->label('Chiqarish')
                    ->icon('heroicon-o-arrow-right-on-rectangle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (User $record) {
                        $this->getOwnerRecord()->students()->updateExistingPivot($record->id, [
                            'is_active' => false,
                            'left_at'   => today(),
                        ]);
                        Notification::make()->title('O\'quvchi guruhdan chiqarildi')->warning()->send();
                    })
                    ->visible(fn (User $record) => (bool) $record->pivot->is_active),
            ]);
    }
}
