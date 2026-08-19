<?php

namespace App\Filament\Pages\Auth;

use Filament\Auth\Pages\EditProfile as BaseEditProfile;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/**
 * The stock profile page, plus somewhere to turn the email down.
 *
 * Only email is switchable. The bell stays on: it is the record of what
 * happened and costs nothing to scroll past, whereas email is the part that
 * interrupts somebody's day.
 */
class EditProfile extends BaseEditProfile
{
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getNameFormComponent(),
                $this->getEmailFormComponent(),
                $this->getPasswordFormComponent(),
                $this->getPasswordConfirmationFormComponent(),
                $this->getCurrentPasswordFormComponent(),

                Section::make(__('Ειδοποιήσεις'))
                    ->description(__('Το καμπανάκι μέσα στην εφαρμογή δεν απενεργοποιείται — εκεί μένει το ιστορικό.'))
                    ->schema([
                        Toggle::make('notify_by_email')
                            ->label(__('Ειδοποιήσεις με email'))
                            ->helperText(__('Αναθέσεις, σχόλια, ολοκληρώσεις και υπενθυμίσεις προθεσμιών.')),

                        Toggle::make('notify_weekly_digest')
                            ->label(__('Εβδομαδιαία σύνοψη'))
                            ->helperText(__('Κάθε Δευτέρα πρωί, με το πού βρίσκονται οι εργασίες. Δεν στέλνεται αν δεν υπάρχει τίποτα.')),
                    ])
                    ->columns(1),
            ]);
    }
}
