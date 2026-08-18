<?php

namespace App\Filament\Platform\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class PlatformGuide extends Page
{
    protected string $view = 'filament.platform.pages.platform-guide';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAcademicCap;

    protected static ?int $navigationSort = 91;

    public static function getNavigationLabel(): string
    {
        return __('Οδηγός Χρήσης');
    }

    public function getTitle(): string
    {
        return __('Οδηγός Χρήσης');
    }
}
