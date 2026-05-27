<?php

namespace App\Filament\Resources\Leads\Tables;

use App\Enums\LeadStatus;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class LeadsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('captured_at', 'desc')
            ->columns([
                TextColumn::make('captured_at')
                    ->label('Captured')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),

                TextColumn::make('platform')
                    ->badge()
                    ->color('gray')
                    ->searchable(),

                TextColumn::make('platform_lead_id')
                    ->label('Source ID')
                    ->limit(20)
                    ->copyable()
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('campaign_id')
                    ->label('Campaign')
                    ->limit(15)
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (LeadStatus $state): string => match ($state) {
                        LeadStatus::Pending => 'gray',
                        LeadStatus::Processing => 'warning',
                        LeadStatus::Processed => 'success',
                        LeadStatus::Failed => 'danger',
                        LeadStatus::Skipped => 'info',
                    }),

                TextColumn::make('contact.last_name')
                    ->label('Contact')
                    ->formatStateUsing(function ($state, $record) {
                        if (! $record->contact) {
                            return '—';
                        }

                        return trim($record->contact->first_name.' '.$record->contact->last_name)
                            ?: ('#'.$record->contact->id);
                    })
                    ->url(fn ($record) => $record->contact_id
                        ? route('filament.admin.resources.contacts.edit', $record->contact_id)
                        : null
                    ),

                TextColumn::make('processed_at')
                    ->label('Processed')
                    ->dateTime('Y-m-d H:i')
                    ->placeholder('—')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('agency.name')
                    ->label('Agency')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(LeadStatus::class),

                SelectFilter::make('platform')
                    ->options(fn () => \App\Models\Lead::query()
                        ->select('platform')
                        ->distinct()
                        ->pluck('platform', 'platform')),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
