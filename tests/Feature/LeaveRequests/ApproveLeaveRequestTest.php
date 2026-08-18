<?php

use App\Filament\Resources\LeaveRequests\Pages\EditLeaveRequest;
use App\Filament\Resources\LeaveRequests\Pages\ListLeaveRequests;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Tenant;
use App\Models\User;
use App\Services\LeaveBalanceService;
use Livewire\Livewire;

/**
 * Submitting a request only checks the balance against already-APPROVED leave,
 * so several pending requests can each fit on their own and still overdraw the
 * balance once they are approved. Approval is the last line of defence.
 */
it('blocks a quick approval that would overdraw the remaining balance', function () {
    $tenant = Tenant::factory()->create();
    $admin = User::factory()->for($tenant)->admin()->create();
    $employee = User::factory()->for($tenant)->create();
    $leaveType = LeaveType::factory()->for($tenant)->create([
        'auto_calculate' => false,
        'fixed_days_per_year' => 5,
    ]);

    $first = LeaveRequest::factory()->for($employee)->for($leaveType)->create([
        'start_date' => '2026-11-02', 'end_date' => '2026-11-04', 'days_count' => 4,
        'status' => LeaveRequest::STATUS_PENDING,
    ]);
    $second = LeaveRequest::factory()->for($employee)->for($leaveType)->create([
        'start_date' => '2026-11-09', 'end_date' => '2026-11-11', 'days_count' => 3,
        'status' => LeaveRequest::STATUS_PENDING,
    ]);

    actingInTenant($admin);

    Livewire::test(ListLeaveRequests::class)->callTableAction('approve', $first);
    Livewire::test(ListLeaveRequests::class)->callTableAction('approve', $second);

    expect($first->fresh()->status)->toBe(LeaveRequest::STATUS_APPROVED)
        ->and($second->fresh()->status)->toBe(LeaveRequest::STATUS_PENDING);

    $service = app(LeaveBalanceService::class);
    expect($service->usedDays($employee, $leaveType, 2026))->toBe(4.0)
        ->and($service->remainingDays($employee, $leaveType, 2026))->toBeGreaterThanOrEqual(0.0);
});

it('allows a quick approval that exactly fits the remaining balance', function () {
    $tenant = Tenant::factory()->create();
    $admin = User::factory()->for($tenant)->admin()->create();
    $employee = User::factory()->for($tenant)->create();
    $leaveType = LeaveType::factory()->for($tenant)->create([
        'auto_calculate' => false,
        'fixed_days_per_year' => 3,
    ]);

    $request = LeaveRequest::factory()->for($employee)->for($leaveType)->create([
        'start_date' => '2026-11-02', 'end_date' => '2026-11-04', 'days_count' => 3,
        'status' => LeaveRequest::STATUS_PENDING,
    ]);

    actingInTenant($admin);

    Livewire::test(ListLeaveRequests::class)->callTableAction('approve', $request);

    expect($request->fresh()->status)->toBe(LeaveRequest::STATUS_APPROVED);
});

it('blocks an admin approving via the edit page past the remaining balance', function () {
    $tenant = Tenant::factory()->create();
    $admin = User::factory()->for($tenant)->admin()->create();
    $employee = User::factory()->for($tenant)->create();
    $leaveType = LeaveType::factory()->for($tenant)->create([
        'auto_calculate' => false,
        'fixed_days_per_year' => 5,
    ]);

    LeaveRequest::factory()->for($employee)->for($leaveType)->approved()->create([
        'start_date' => '2026-11-02', 'end_date' => '2026-11-04', 'days_count' => 4,
    ]);
    $pending = LeaveRequest::factory()->for($employee)->for($leaveType)->create([
        'start_date' => '2026-11-09', 'end_date' => '2026-11-11', 'days_count' => 3,
        'status' => LeaveRequest::STATUS_PENDING,
    ]);

    actingInTenant($admin);

    Livewire::test(EditLeaveRequest::class, ['record' => $pending->getKey()])
        ->set('data.status', LeaveRequest::STATUS_APPROVED)
        ->call('save');

    expect($pending->fresh()->status)->toBe(LeaveRequest::STATUS_PENDING)
        ->and(app(LeaveBalanceService::class)->usedDays($employee, $leaveType, 2026))->toBe(4.0);
});

it('does not count a request against its own allowance when re-approving it', function () {
    $tenant = Tenant::factory()->create();
    $admin = User::factory()->for($tenant)->admin()->create();
    $employee = User::factory()->for($tenant)->create();
    $leaveType = LeaveType::factory()->for($tenant)->create([
        'auto_calculate' => false,
        'fixed_days_per_year' => 5,
    ]);

    // Already approved and using the whole entitlement — saving it again
    // unchanged must not be rejected as if it were a brand-new 5-day request.
    $approved = LeaveRequest::factory()->for($employee)->for($leaveType)->approved()->create([
        'start_date' => '2026-11-02', 'end_date' => '2026-11-06', 'days_count' => 5,
    ]);

    actingInTenant($admin);

    Livewire::test(EditLeaveRequest::class, ['record' => $approved->getKey()])
        ->set('data.note', 'touched')
        ->call('save');

    expect($approved->fresh()->status)->toBe(LeaveRequest::STATUS_APPROVED);
});
