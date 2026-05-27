<?php

namespace App\Filament\Resources\Agencies\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class AgenciesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('slug')
                    ->copyable()
                    ->color('gray')
                    ->searchable(),

                IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active'),

                TextColumn::make('users_count')
                    ->label('Users')
                    ->counts('users')
                    ->numeric()
                    ->alignCenter(),

                TextColumn::make('contacts_count')
                    ->label('Contacts')
                    ->counts('contacts')
                    ->numeric()
                    ->alignCenter(),

                TextColumn::make('leads_count')
                    ->label('Leads')
                    ->counts('leads')
                    ->numeric()
                    ->alignCenter(),

                TextColumn::make('created_at')
                    ->dateTime('Y-m-d')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
