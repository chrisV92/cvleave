<?php

namespace App\Filament\Exports;

use App\Models\LeaveRequest;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class LeaveRequestExporter extends Exporter
{
    protected static ?string $model = LeaveRequest::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('user.name')
                ->label(__('Υπάλληλος')),
            ExportColumn::make('leaveType.name')
                ->label(__('Τύπος άδειας')),
            ExportColumn::make('start_date')
                ->label(__('Από'))
                ->formatStateUsing(fn ($state) => $state?->format('d/m/Y')),
            ExportColumn::make('end_date')
                ->label(__('Έως'))
                ->formatStateUsing(fn ($state) => $state?->format('d/m/Y')),
            ExportColumn::make('duration_type')
                ->label(__('Τύπος Διάρκειας'))
                ->formatStateUsing(fn (string $state) => match ($state) {
                    'half_day' => __('Μισή Μέρα'),
                    'hours' => __('Ώρες'),
                    default => __('Ολόκληρη Μέρα'),
                }),
            ExportColumn::make('hours')
                ->label(__('Ώρες')),
            ExportColumn::make('days_count')
                ->label(__('Μέρες')),
            ExportColumn::make('status')
                ->label(__('Κατάσταση'))
                ->formatStateUsing(fn (string $state) => match ($state) {
                    'pending' => __('Εκκρεμεί'),
                    'approved' => __('Εγκρίθηκε'),
                    'rejected' => __('Απορρίφθηκε'),
                    'cancelled' => __('Ακυρώθηκε'),
                    default => $state,
                }),
            ExportColumn::make('note')
                ->label(__('Σημείωση')),
            ExportColumn::make('rejection_reason')
                ->label(__('Αιτία απόρριψης')),
            ExportColumn::make('reviewer.name')
                ->label(__('Έλεγχος από')),
            ExportColumn::make('reviewed_at')
                ->label(__('Ημερομηνία ελέγχου'))
                ->formatStateUsing(fn ($state) => $state?->format('d/m/Y H:i')),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = __(':count αιτήσεις εξήχθησαν επιτυχώς.', ['count' => $export->successful_rows]);

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' '.__(':count απέτυχαν.', ['count' => $failedRowsCount]);
        }

        return $body;
    }
}
