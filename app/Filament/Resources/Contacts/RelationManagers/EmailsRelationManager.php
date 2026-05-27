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

class EmailsRelationManager extends RelationManager
{
    protected static string $relationship = 'emails';

    protected static ?string $title = 'Emails';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('email')
                    ->email()
                    ->required()
                    ->maxLength(255),
                TextInput::make('label')
                    ->maxLength(50)
                    ->placeholder('personal, work, etc.'),
                Toggle::make('is_primary')
                    ->helperText('Marks this as the primary email for the contact.'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('email')
            ->columns([
                TextColumn::make('email')
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
