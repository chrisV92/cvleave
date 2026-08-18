<?php

use App\Filament\Platform\Resources\Users\Pages\ListUsers;
use App\Models\ImpersonationLog;
use App\Models\Tenant;
use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;
use STS\FilamentImpersonate\Facades\Impersonation;

function actingAsPlatformAdmin(User $user): User
{
    test()->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('platform'));

    return $user;
}

it('lets a platform admin impersonate a tenant user and logs it', function () {
    $tenant = Tenant::factory()->create();
    $superadmin = User::factory()->for($tenant)->create(['is_platform_admin' => true]);
    $target = User::factory()->for($tenant)->create();

    actingAsPlatformAdmin($superadmin);

    Livewire::test(ListUsers::class)
        ->callTableAction('impersonate', $target);

    expect(auth()->id())->toBe($target->id)
        ->and(Impersonation::isImpersonating())->toBeTrue()
        ->and(Impersonation::getImpersonatorId())->toBe($superadmin->id);

    $log = ImpersonationLog::first();
    expect($log)->not->toBeNull()
        ->and($log->impersonator_id)->toBe($superadmin->id)
        ->and($log->impersonated_id)->toBe($target->id)
        ->and($log->tenant_id)->toBe($tenant->id)
        ->and($log->ended_at)->toBeNull();
});

it('does not let a non-platform-admin see or trigger the impersonate action', function () {
    $tenant = Tenant::factory()->create();
    $admin = User::factory()->for($tenant)->admin()->create();
    $target = User::factory()->for($tenant)->create();

    actingAsPlatformAdmin($admin);

    Livewire::test(ListUsers::class)
        ->assertTableActionHidden('impersonate', $target);
});

it('does not let a platform admin impersonate another platform admin', function () {
    $tenant = Tenant::factory()->create();
    $superadminA = User::factory()->for($tenant)->create(['is_platform_admin' => true]);
    $superadminB = User::factory()->for($tenant)->create(['is_platform_admin' => true]);

    actingAsPlatformAdmin($superadminA);

    Livewire::test(ListUsers::class)
        ->assertTableActionHidden('impersonate', $superadminB);
});
