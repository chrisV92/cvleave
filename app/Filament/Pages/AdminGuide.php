<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class AdminGuide extends Page
{
    protected string $view = 'filament.pages.admin-guide';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAcademicCap;

    protected static ?int $navigationSort = 91;

    public static function getNavigationLabel(): string
    {
        return __('Οδηγός Διαχειριστή');
    }

    public function getTitle(): string
    {
        return __('Οδηγός Διαχειριστή');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }
}
