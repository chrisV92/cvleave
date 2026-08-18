<?php

use App\Filament\Platform\Widgets\PlatformGrowthChart;
use App\Filament\Platform\Widgets\PlatformOverview;
use App\Filament\Platform\Widgets\TenantActivity;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Tenant;
use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;

beforeEach(function () {
    $this->tenant = Tenant::factory()->create(['name' => 'Acme Co']);
    $this->superadmin = User::factory()->for($this->tenant)->create(['is_platform_admin' => true]);

    test()->actingAs($this->superadmin);
    Filament::setCurrentPanel(Filament::getPanel('platform'));
});

it('renders the platform dashboard with its widgets', function () {
    $response = $this->get('/platform');

    $response->assertSuccessful();

    // The dashboard used to be completely empty; assert the widgets are
    // actually mounted rather than just that the page loads.
    expect(Filament::getPanel('platform')->getWidgets())
        ->toContain(PlatformOverview::class)
        ->toContain(PlatformGrowthChart::class)
        ->toContain(TenantActivity::class);

    // Widgets are Livewire components the page only lazily embeds, so a 200 on
    // the dashboard says nothing about whether they render. Mount each one.
    Livewire::test(PlatformOverview::class)
        ->assertSuccessful()
        ->assertSee(__('Εταιρείες'));

    Livewire::test(PlatformGrowthChart::class)
        ->assertSuccessful()
        ->assertSee(__('Νέες εταιρείες ανά μήνα'));

    Livewire::test(TenantActivity::class)
        ->assertSuccessful()
        ->assertSee($this->tenant->name);
});

it('counts companies, users and requests across every tenant', function () {
    // The backfill migration seeds a "Default" tenant into the test database,
    // so measure the movement rather than assuming the world starts empty.
    $before = [
        'tenants' => Tenant::count(),
        'users' => User::count(),
        'requests' => LeaveRequest::whereYear('start_date', now()->year)->count(),
    ];

    $otherTenant = Tenant::factory()->create();
    $employee = User::factory()->for($otherTenant)->create();
    $leaveType = LeaveType::factory()->for($otherTenant)->create();

    LeaveRequest::factory()->for($employee)->for($leaveType)->create([
        'start_date' => now(), 'end_date' => now()->addDay(),
        'status' => LeaveRequest::STATUS_PENDING,
    ]);

    $stats = invade(Livewire::test(PlatformOverview::class)->instance())->getCachedStats();
    $values = collect($stats)->map(fn ($stat) => $stat->getValue())->all();

    // Every one of those additions sits outside the superadmin's own tenant, so
    // if a tenancy scope ever leaked into these widgets the totals would not move.
    expect($values[0])->toBe($before['tenants'] + 1)
        ->and($values[1])->toBe($before['users'] + 1)
        ->and($values[3])->toBe($before['requests'] + 1);
});

it('reports per-company activity including companies with none', function () {
    $activeTenant = Tenant::factory()->create(['name' => 'Busy Ltd']);
    $employee = User::factory()->for($activeTenant)->create();
    $leaveType = LeaveType::factory()->for($activeTenant)->create();
    LeaveRequest::factory()->for($employee)->for($leaveType)->create();

    $rows = Livewire::test(TenantActivity::class)
        ->instance()
        ->getTable()
        ->getQuery()
        ->get()
        ->keyBy('name');

    expect((int) $rows['Busy Ltd']->leave_requests_count)->toBe(1)
        ->and($rows['Busy Ltd']->last_request_at)->not->toBeNull()
        // A company that has never filed a request must still be listed —
        // silent customers are exactly the ones worth spotting.
        ->and((int) $rows['Acme Co']->leave_requests_count)->toBe(0)
        ->and($rows['Acme Co']->last_request_at)->toBeNull();
});

it('plots twelve months of company signups', function () {
    Tenant::factory()->create(['created_at' => now()->subMonths(2)]);
    Tenant::factory()->create(['created_at' => now()->subMonths(2)]);

    $data = invade(Livewire::test(PlatformGrowthChart::class)->instance())->getCachedData();

    expect($data['labels'])->toHaveCount(12)
        ->and($data['datasets'])->toHaveCount(1)
        ->and($data['datasets'][0]['data'])->toHaveCount(12)
        ->and($data['datasets'][0]['data'][9])->toBe(2); // two months back
});
