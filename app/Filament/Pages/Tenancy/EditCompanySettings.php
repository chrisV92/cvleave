<?php

namespace App\Filament\Pages\Tenancy;

use App\Support\Permissions;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Pages\Tenancy\EditTenantProfile;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

class EditCompanySettings extends EditTenantProfile
{
    public static function getLabel(): string
    {
        return __('Ρυθμίσεις Εταιρείας');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label(__('Όνομα'))
                    ->required()
                    ->maxLength(255),

                Section::make(__('Μεταφορά Υπολοίπου Αδειών'))
                    ->description(__('Μέχρι πότε μέσα στο νέο έτος μπορούν οι υπάλληλοι να χρησιμοποιήσουν τις αχρησιμοποίητες μέρες του προηγούμενου έτους. Στην Ελλάδα συνηθίζεται η 31η Μαρτίου — επιβεβαίωσε τι ισχύει για την εταιρεία σου.'))
                    ->schema([
                        Toggle::make('allows_carryover')
                            ->label(__('Επιτρέπεται μεταφορά υπολοίπου στο επόμενο έτος'))
                            ->live()
                            ->dehydrated(false)
                            ->afterStateHydrated(fn ($component, $record) => $component->state((bool) $record?->carryover_deadline_month)),

                        Select::make('carryover_deadline_month')
                            ->label(__('Μήνας προθεσμίας'))
                            ->options([
                                1 => __('Ιανουάριος'), 2 => __('Φεβρουάριος'), 3 => __('Μάρτιος'),
                                4 => __('Απρίλιος'), 5 => __('Μάιος'), 6 => __('Ιούνιος'),
                                7 => __('Ιούλιος'), 8 => __('Αύγουστος'), 9 => __('Σεπτέμβριος'),
                                10 => __('Οκτώβριος'), 11 => __('Νοέμβριος'), 12 => __('Δεκέμβριος'),
                            ])
                            ->default(3)
                            ->visible(fn ($get) => (bool) $get('allows_carryover'))
                            ->required(fn ($get) => (bool) $get('allows_carryover')),

                        TextInput::make('carryover_from_year')
                            ->label(__('Ισχύει για υπόλοιπα από το έτος'))
                            ->numeric()
                            ->minValue(2000)
                            ->maxValue((int) now()->year + 1)
                            ->default((int) now()->year)
                            // Companies that enabled carry-over before this
                            // setting existed have it unset; prefill the safe
                            // answer rather than showing an empty required box.
                            ->afterStateHydrated(fn ($component, $state) => $component->state($state ?? (int) now()->year))
                            ->helperText(__('Το πρώτο έτος για το οποίο η εφαρμογή έχει πλήρη καταγραφή αδειών. Αν ξεκινήσατε φέτος, άφησέ το στο τρέχον έτος — αλλιώς οι υπάλληλοι θα εμφανιστούν να μεταφέρουν ολόκληρο έτος που δεν καταγράφηκε ποτέ.'))
                            ->visible(fn ($get) => (bool) $get('allows_carryover'))
                            ->required(fn ($get) => (bool) $get('allows_carryover')),

                        TextInput::make('carryover_deadline_day')
                            ->label(__('Ημέρα προθεσμίας'))
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(31)
                            ->default(31)
                            ->helperText(__('Αν ο μήνας έχει λιγότερες μέρες, χρησιμοποιείται η τελευταία του.'))
                            ->visible(fn ($get) => (bool) $get('allows_carryover'))
                            ->required(fn ($get) => (bool) $get('allows_carryover')),
                    ]),
            ]);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Clearing the toggle hides both fields, which is how carry-over gets
        // switched off — the model treats "no deadline" as "no carry-over".
        if (blank($data['carryover_deadline_month'] ?? null) || blank($data['carryover_deadline_day'] ?? null)) {
            $data['carryover_deadline_month'] = null;
            $data['carryover_deadline_day'] = null;
            $data['carryover_from_year'] = null;
        }

        return $data;
    }

    protected function getSaveFormAction(): Action
    {
        // Filament ships no Greek string for this one, so it would otherwise
        // sit in English among translated fields.
        return parent::getSaveFormAction()->label(__('Αποθήκευση'));
    }

    public static function canView(Model $tenant): bool
    {
        return auth()->user()?->can(Permissions::COMPANY_SETTINGS) ?? false;
    }
}
