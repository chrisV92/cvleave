<?php

use App\Filament\Pages\Tenancy\EditCompanySettings;
use App\Models\Tenant;
use App\Models\User;
use Livewire\Livewire;

it('lets a tenant admin configure the carry-over window', function () {
    $tenant = Tenant::factory()->create();
    $admin = User::factory()->for($tenant)->admin()->create();

    actingInTenant($admin);

    Livewire::test(EditCompanySettings::class)
        ->assertFormFieldExists('carryover_deadline_month')
        ->assertFormFieldExists('carryover_deadline_day')
        ->assertFormFieldExists('carryover_from_year')
        ->fillForm([
            'name' => $tenant->name,
            'allows_carryover' => true,
            'carryover_deadline_month' => 3,
            'carryover_deadline_day' => 31,
            'carryover_from_year' => 2026,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $tenant->refresh();

    expect($tenant->carryover_deadline_month)->toBe(3)
        ->and($tenant->carryover_deadline_day)->toBe(31)
        ->and($tenant->carryover_from_year)->toBe(2026)
        ->and($tenant->carryoverDeadlineFor(2026)->toDateString())->toBe('2026-03-31');
});

it('clears the whole carry-over configuration when it is switched off', function () {
    $tenant = Tenant::factory()->create([
        'carryover_deadline_month' => 3,
        'carryover_deadline_day' => 31,
        'carryover_from_year' => 2026,
    ]);
    $admin = User::factory()->for($tenant)->admin()->create();

    actingInTenant($admin);

    Livewire::test(EditCompanySettings::class)
        ->fillForm([
            'name' => $tenant->name,
            'allows_carryover' => false,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $tenant->refresh();

    expect($tenant->carryover_deadline_month)->toBeNull()
        ->and($tenant->carryover_deadline_day)->toBeNull()
        ->and($tenant->carryover_from_year)->toBeNull()
        ->and($tenant->allowsCarryover())->toBeFalse();
});
