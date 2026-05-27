<?php

namespace App\Filament\Resources\Contacts\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PhonesRelationManager extends RelationManager
{
    protected static string $relationship = 'phones';

    protected static ?string $title = 'Phones';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('phone')
                    ->numeric()
                    ->required()
                    ->minValue(1)
                    ->helperText('Digits only — no spaces, dashes, or "+" prefix.'),
                TextInput::make('label')
                    ->maxLength(50)
                    ->placeholder('mobile, work, dad, etc.'),
                Toggle::make('is_primary')
                    ->helperText('Marks this as the primary phone for the contact.'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('phone')
            ->columns([
                TextColumn::make('phone')
                    ->copyable()
                    ->searchable(),
                TextColumn::make('label')
                    ->badge(),
                IconColumn::make('is_primary')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->dateTime('Y-m-d H:i')
                    ->toggleable(),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
