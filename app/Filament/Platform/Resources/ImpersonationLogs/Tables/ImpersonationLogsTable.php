<?php

namespace App\Filament\Platform\Resources\ImpersonationLogs\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ImpersonationLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('started_at', 'desc')
            ->columns([
                TextColumn::make('impersonator.name')
                    ->label(__('Platform Admin'))
                    ->searchable(),
                TextColumn::make('impersonated.name')
                    ->label(__('Χρήστης'))
                    ->searchable(),
                TextColumn::make('tenant.name')
                    ->label(__('Εταιρεία'))
                    ->searchable(),
                TextColumn::make('started_at')
                    ->label(__('Ξεκίνησε'))
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('ended_at')
                    ->label(__('Έληξε'))
                    ->dateTime('d/m/Y H:i')
                    ->placeholder(__('— σε εξέλιξη —'))
                    ->sortable(),
            ]);
    }
}
