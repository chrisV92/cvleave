<?php

namespace App\Filament\Resources\CustomFields\Pages;

use App\Filament\Resources\CustomFields\CustomFieldResource;
use App\Models\CustomField;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Filament\Resources\Pages\ListRecords;

class ListCustomFields extends ListRecords
{
    protected static string $resource = CustomFieldResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('Προσθήκη Πεδίου'))
                ->visible(fn () => CustomFieldResource::canCreate())
                ->mutateDataUsing(function (array $data): array {
                    $tenantId = Filament::getTenant()?->id;

                    $data['tenant_id'] = $tenantId;
                    // Null is what makes it company-wide rather than a board's.
                    $data['project_id'] = null;
                    $data['position'] = (CustomField::query()
                        ->where('tenant_id', $tenantId)
                        ->companyWide()
                        ->max('position') ?? -1) + 1;

                    return $data;
                }),
        ];
    }
}
