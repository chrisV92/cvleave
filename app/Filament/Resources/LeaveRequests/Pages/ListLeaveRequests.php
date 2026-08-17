<?php

namespace App\Filament\Resources\LeaveRequests\Pages;

use App\Filament\Exports\LeaveRequestExporter;
use App\Filament\Resources\LeaveRequests\LeaveRequestResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\ExportAction;
use Filament\Resources\Pages\ListRecords;

class ListLeaveRequests extends ListRecords
{
    protected static string $resource = LeaveRequestResource::class;

    protected function getHeaderActions(): array
    {
        $isAdmin = auth()->user()?->isAdmin() ?? false;

        return [
            $isAdmin
                ? Action::make('allEmployeesPdfReport')
                    ->label(__('Αναφορά Όλων (PDF)'))
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('gray')
                    ->url(fn () => route('reports.all-employees-leave'))
                    ->openUrlInNewTab()
                : Action::make('myPdfReport')
                    ->label(__('Η Αναφορά μου (PDF)'))
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('gray')
                    ->url(fn () => auth()->check() ? route('reports.employee-leave', auth()->id()) : null)
                    ->openUrlInNewTab(),
            ExportAction::make()
                ->label(__('Εξαγωγή Excel'))
                ->exporter(LeaveRequestExporter::class),
            CreateAction::make(),
        ];
    }
}
