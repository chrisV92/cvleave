<?php

namespace App\Filament\Resources\Projects\Pages;

use App\Filament\Resources\Projects\ProjectResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListProjects extends ListRecords
{
    protected static string $resource = ProjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // The create page aborts with a 403 on its own, but offering a
            // button that only leads there is a worse answer than not
            // offering it.
            CreateAction::make()
                ->visible(fn () => ProjectResource::canCreate()),
        ];
    }
}
