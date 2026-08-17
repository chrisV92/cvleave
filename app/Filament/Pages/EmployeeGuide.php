<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class EmployeeGuide extends Page
{
    protected string $view = 'filament.pages.employee-guide';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    protected static ?int $navigationSort = 90;

    public static function getNavigationLabel(): string
    {
        return __('Οδηγός Χρήσης');
    }

    public function getTitle(): string
    {
        return __('Οδηγός Χρήσης');
    }
}
