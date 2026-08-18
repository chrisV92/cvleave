<?php

use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\LeaveEndingSoon;
use App\Notifications\LeaveRequestReviewed;
use App\Notifications\LeaveRequestSubmitted;
use App\Notifications\LeaveStartingSoon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/**
 * Panel routes are /admin/{tenant}/..., so the hand-written /admin/leave-requests
 * links in these emails silently started 404ing when multi-tenancy landed.
 * Asserting the CTA resolves to a real route is what would have caught it.
 */
function ctaPathFor(object $notification, User $notifiable): string
{
    $mail = $notification->toMail($notifiable);

    return '/'.ltrim(parse_url($mail->viewData['ctaUrl'], PHP_URL_PATH) ?? '', '/');
}

function assertPathResolves(string $path): void
{
    $matched = collect(Route::getRoutes()->getRoutesByMethod()['GET'] ?? [])
        ->contains(fn ($route) => $route->matches(
            Request::create($path, 'GET'), includingMethod: false
        ));

    expect($matched)->toBeTrue("No GET route matches {$path}");
}

beforeEach(function () {
    $this->tenant = Tenant::factory()->create(['slug' => 'acme-co']);
    $this->employee = User::factory()->for($this->tenant)->create();
    $this->admin = User::factory()->for($this->tenant)->admin()->create();
    $this->leaveType = LeaveType::factory()->for($this->tenant)->create();

    $this->leaveRequest = LeaveRequest::factory()
        ->for($this->employee)
        ->for($this->leaveType)
        ->approved()
        ->create(['start_date' => now()->addDay(), 'end_date' => now()->addDays(3)]);
});

it('points the new-request email at the recipient\'s own tenant panel', function () {
    $path = ctaPathFor(new LeaveRequestSubmitted($this->leaveRequest), $this->admin);

    expect($path)->toBe('/admin/acme-co/leave-requests');
    assertPathResolves($path);
});

it('points the reviewed-request email at the recipient\'s own tenant panel', function () {
    $path = ctaPathFor(new LeaveRequestReviewed($this->leaveRequest), $this->employee);

    expect($path)->toBe('/admin/acme-co/leave-requests');
    assertPathResolves($path);
});

it('points the reminder emails at the recipient\'s own tenant dashboard', function (string $notificationClass) {
    $path = ctaPathFor(new $notificationClass($this->leaveRequest), $this->employee);

    expect($path)->toBe('/admin/acme-co');
    assertPathResolves($path);
})->with([
    LeaveStartingSoon::class,
    LeaveEndingSoon::class,
]);

it('falls back to the panel root when no tenant can be resolved', function () {
    // users.tenant_id is NOT NULL, so this state cannot be persisted — an
    // unsaved model is the honest way to exercise the defensive branch, which
    // lets Filament's own redirect work out where to send them.
    $path = ctaPathFor(new LeaveStartingSoon($this->leaveRequest), new User);

    expect($path)->toBe('/admin');
    assertPathResolves($path);
});
