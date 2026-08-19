<?php

use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->beforeEach(function () {
        // Filament's manager keeps the current panel and tenant in memory, and
        // nothing resets them between tests the way a new request would. Left
        // over, they are not merely stale context: the tenancy observer
        // *overwrites* tenant_id on every record created while a tenant is
        // current, so a factory call at the top of one test would silently be
        // stamped with the previous test's company.
        Filament::setCurrentPanel(null);
        Filament::setTenant(null, isQuiet: true);
    })
    ->in('Feature');

pest()->extend(TestCase::class)
    ->in('Unit');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * A non-persisted User instance for pure-computation unit tests
 * (e.g. the Greek annual leave formula) that don't need the database.
 */
function unpersistedUser(?string $hireDate, float $priorYears = 0): User
{
    $user = new User;
    $user->hire_date = $hireDate ? Carbon::parse($hireDate) : null;
    $user->prior_experience_years = $priorYears;

    return $user;
}

/**
 * Logs in as $user and establishes the Filament tenant + Spatie permissions team
 * context for their tenant, mirroring what the SetPermissionsTeamId middleware
 * does on a real request. Needed by any Feature test that hits a tenant-scoped
 * Filament panel resource/page directly via Livewire::test().
 */
function actingInTenant(User $user): User
{
    test()->actingAs($user);

    // Booting the panel is what registers Filament's tenancy global scope and
    // the observer that stamps tenant_id on new records. A real request gets
    // that from the panel middleware; a Livewire test does not, and without it
    // every resource query runs completely unscoped — isolation tests would
    // pass only because nothing was scoped in either direction.
    //
    // It has to happen on every call, not once: each test builds a fresh
    // container and therefore a fresh Panel instance, while global scopes live
    // on the model classes and survive. The scope closure compares the current
    // panel against the instance it captured, so a scope registered in an
    // earlier test silently does nothing in this one. Clearing the booted
    // models drops those stale registrations so they rebind to this test's
    // panel.
    Model::clearBootedModels();

    $panel = Filament::getPanel('admin');
    $panel->boot();

    Filament::setCurrentPanel($panel);
    Filament::setTenant($user->tenant, isQuiet: true);
    app(PermissionRegistrar::class)->setPermissionsTeamId($user->tenant_id);

    return $user;
}
