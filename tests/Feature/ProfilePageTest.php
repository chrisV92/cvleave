<?php

use App\Filament\Pages\Auth\EditProfile;
use App\Models\Tenant;
use App\Models\User;

it('opens the profile page, which sits outside any company', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->for($tenant)->admin()->create();

    // Deliberately not actingInTenant(): /admin/profile carries no {tenant}
    // segment, so a real request arrives with no current tenant at all. Setting
    // one here would hide exactly the case that breaks.
    $this->actingAs($user);

    $this->get('/admin/profile')->assertSuccessful();
});

it('saves the notification preferences from the profile', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->for($tenant)->create();

    $this->actingAs($user);

    Livewire\Livewire::test(EditProfile::class)
        ->assertFormFieldExists('notify_by_email')
        ->assertFormFieldExists('notify_weekly_digest')
        ->fillForm([
            'name' => $user->name,
            'email' => $user->email,
            'notify_by_email' => false,
            'notify_weekly_digest' => false,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($user->fresh()->notify_by_email)->toBeFalse()
        ->and($user->fresh()->notify_weekly_digest)->toBeFalse();
});

it('still refuses somebody who is not signed in', function () {
    $this->get('/admin/profile')->assertRedirect();
});
