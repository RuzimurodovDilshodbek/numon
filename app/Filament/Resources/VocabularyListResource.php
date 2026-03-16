<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Resources\Resource;
use App\Models\VocabularyList;
use App\Models\VocabularyWord;
use Filament\Forms\Form;
use Filament\Tables\Table;
use League\Csv\Reader;
use Illuminate\Support\Facades\DB;

class VocabularyListResource extends Resource
{
    protected static ?string $model = VocabularyList::class;
    protected static ?string $navigationLabel = "Lug'atlar";
    protected static ?string $navigationIcon = 'heroicon-o-book-open';
    protected static ?string $navigationGroup = 'Boshqaruv';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('title')->label('Sarlavha')->required(),
            Forms\Components\Textarea::make('description')->label('Tavsif'),

            Forms\Components\Section::make('CSV Import')->schema([
                Forms\Components\FileUpload::make('csv_file')
                    ->label("CSV fayl (turk;o'zbek;misol;daraja)")
                    ->acceptedFileTypes(['text/csv', 'text/plain'])
                    ->disk('local')
                    ->directory('csv-imports')
                    ->helperText('Format: turkish_word,uzbek_translation,example_sentence,difficulty_level'),
            ])->visibleOn('create'),

            Forms\Components\Section::make("So'zlar")->schema([
                Forms\Components\Repeater::make('words')
                    ->relationship()
                    ->label("So'zlar ro'yxati")
                    ->schema([
                        Forms\Components\TextInput::make('turkish_word')->label("Turk so'z")->required(),
                        Forms\Components\TextInput::make('uzbek_translation')->label("O'zbekcha")->required(),
                        Forms\Components\TextInput::make('example_sentence')->label('Misol gap'),
                        Forms\Components\Select::make('difficulty_level')
                            ->label('Daraja')
                            ->options([1 => '1', 2 => '2', 3 => '3', 4 => '4', 5 => '5'])
                            ->default(1),
                    ])
                    ->columns(4)
                    ->addActionLabel("So'z qo'shish"),
            ])->visibleOn('edit'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('title')->label('Sarlavha')->searchable(),
            Tables\Columns\TextColumn::make('words_count')->label("So'zlar soni")->counts('words'),
            Tables\Columns\TextColumn::make('created_at')->label('Yaratildi')->date(),
        ])
        ->actions([
            Tables\Actions\ViewAction::make(),
            Tables\Actions\EditAction::make(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Resources\VocabularyListResource\Pages\ListVocabularyLists::route('/'),
            'create' => \App\Filament\Resources\VocabularyListResource\Pages\CreateVocabularyList::route('/create'),
            'edit' => \App\Filament\Resources\VocabularyListResource\Pages\EditVocabularyList::route('/{record}/edit'),
        ];
    }
}
