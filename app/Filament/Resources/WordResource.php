<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WordResource\Pages;
use App\Filament\Resources\WordResource\RelationManagers;
use App\Models\Word;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class WordResource extends Resource
{
    protected static ?string $model = Word::class;

    protected static ?string $recordTitleAttribute = 'pronunciation';

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('dictionary')
                    ->columnSpanFull()
                    ->maxLength(255),
                Forms\Components\TextInput::make('text')
                    ->maxLength(255),
                Forms\Components\TextInput::make('pronunciation')
                    ->maxLength(255),
                Forms\Components\Textarea::make('explanation')
                    ->maxLength(65535)
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('url')
                    ->maxLength(65535)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('dictionary')
                    ->searchable(isIndividual: true),
                Tables\Columns\TextColumn::make('text')
                    ->searchable(isIndividual: true),
                Tables\Columns\TextColumn::make('pronunciation')
                    ->searchable(isIndividual: true),
                Tables\Columns\TextColumn::make('explanation')
                    ->searchable(isIndividual: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\Action::make('Url')
                    ->url(fn(Word $record) => $record->url,true),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    ExportBulkAction::make()
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageWords::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        return Word::count();
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['text','pronunciation','explanation'];
    }
}
