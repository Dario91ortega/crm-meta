<?php

namespace App\Filament\Resources\Contacts\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class EventsRelationManager extends RelationManager
{
    protected static string $relationship = 'events';

    protected static ?string $title = 'Timeline';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('occurred_at')
                    ->dateTime('Y-m-d H:i'),
                TextColumn::make('eventable_type')
                    ->badge(),
                TextColumn::make('user.name')
                    ->placeholder('—'),
            ]);
    }
}
