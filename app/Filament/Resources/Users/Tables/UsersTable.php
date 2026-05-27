<?php

namespace App\Filament\Resources\Users\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                ImageColumn::make('avatar')
                    ->label('')
                    ->circular()
                    ->defaultImageUrl(fn ($record) => 'https://ui-avatars.com/api/?name='
                        .urlencode(trim(($record->first_name ?? '').' '.($record->last_name ?? '')) ?: ($record->email ?? '?'))
                        .'&background=0D8ABC&color=fff'),

                TextColumn::make('first_name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('last_name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('email')
                    ->copyable()
                    ->searchable()
                    ->icon(\Filament\Support\Icons\Heroicon::OutlinedEnvelope),

                TextColumn::make('roles.name')
                    ->label('Roles')
                    ->badge()
                    ->separator(','),

                TextColumn::make('agency.name')
                    ->label('Agency')
                    ->sortable()
                    ->placeholder('—'),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),

                IconColumn::make('approved_at')
                    ->label('Approved')
                    ->boolean()
                    ->trueIcon(\Filament\Support\Icons\Heroicon::OutlinedCheckBadge)
                    ->falseIcon(\Filament\Support\Icons\Heroicon::OutlinedClock)
                    ->getStateUsing(fn ($record) => $record->approved_at !== null),

                TextColumn::make('last_login_at')
                    ->dateTime('Y-m-d H:i')
                    ->placeholder('Never')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('roles')
                    ->relationship('roles', 'name')
                    ->multiple()
                    ->preload(),

                Filter::make('pending_approval')
                    ->label('Pending approval')
                    ->query(fn (Builder $q) => $q->whereNull('approved_at')),
            ])
            ->recordActions([
                Action::make('approve')
                    ->label('Approve')
                    ->icon(\Filament\Support\Icons\Heroicon::OutlinedCheckBadge)
                    ->color('success')
                    ->visible(fn ($record) => $record->approved_at === null)
                    ->requiresConfirmation()
                    ->action(fn ($record) => $record->update(['approved_at' => now()])),

                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
