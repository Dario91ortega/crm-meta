<?php

namespace App\Filament\Resources\Contacts\Tables;

use App\Models\User;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class ContactsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('first_name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('last_name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('primaryEmail.email')
                    ->label('Email')
                    ->placeholder('—')
                    ->icon(\Filament\Support\Icons\Heroicon::OutlinedEnvelope)
                    ->copyable()
                    ->searchable(),

                TextColumn::make('primaryPhone.phone')
                    ->label('Phone')
                    ->placeholder('—')
                    ->icon(\Filament\Support\Icons\Heroicon::OutlinedPhone)
                    ->copyable(),

                TextColumn::make('user.name')
                    ->label('Owner')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('agency.name')
                    ->label('Agency')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('user_id')
                    ->label('Owner')
                    ->options(function () {
                        $agencyId = auth()->user()?->agency_id;

                        return User::query()
                            ->when($agencyId, fn ($q) => $q->where('agency_id', $agencyId))
                            ->orderBy('first_name')
                            ->pluck('name', 'id');
                    }),
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
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
