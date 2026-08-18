<?php

use App\Filament\Resources\LeaveRequests\Pages\CreateLeaveRequest;
use App\Filament\Resources\LeaveRequests\Pages\ListLeaveRequests;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Tenant;
use App\Models\User;
use App\Services\LeaveBalanceService;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

beforeEach(function () {
    $this->service = new LeaveBalanceService;

    // Mid-February 2026: inside a 31 March carry-over window.
    Carbon::setTestNow('2026-02-15');

    $this->tenant = Tenant::factory()->create([
        'carryover_deadline_month' => 3,
        'carryover_deadline_day' => 31,
    ]);

    $this->employee = User::factory()->for($this->tenant)->create();

    $this->annual = LeaveType::factory()->for($this->tenant)->create([
        'auto_calculate' => false,
        'fixed_days_per_year' => 10,
        'allows_carryover' => true,
    ]);
});

afterEach(function () {
    Carbon::setTestNow();
});

/** Uses 7 of 2025's 10 days, leaving 3 to carry into 2026. */
function useDaysIn2025(User $employee, LeaveType $type, float $days = 7): void
{
    LeaveRequest::factory()->for($employee)->for($type)->approved()->create([
        'start_date' => '2025-06-01',
        'end_date' => '2025-06-10',
        'days_count' => $days,
        'days_from_carryover' => 0,
    ]);
}

it('offers last year\'s leftover days while the deadline has not passed', function () {
    useDaysIn2025($this->employee, $this->annual);

    expect($this->service->carryoverAvailable($this->employee, $this->annual, '2026-02-20'))->toBe(3.0)
        ->and($this->service->availableFor($this->employee, $this->annual, '2026-02-20'))->toBe(13.0);
});

it('stops offering them once the deadline has passed', function () {
    useDaysIn2025($this->employee, $this->annual);

    expect($this->service->carryoverAvailable($this->employee, $this->annual, '2026-04-01'))->toBe(0.0)
        ->and($this->service->availableFor($this->employee, $this->annual, '2026-04-01'))->toBe(10.0);
});

it('offers nothing when the company has not configured a deadline', function () {
    $this->tenant->update(['carryover_deadline_month' => null, 'carryover_deadline_day' => null]);
    useDaysIn2025($this->employee, $this->annual);

    expect($this->service->carryoverAvailable($this->employee->fresh(), $this->annual, '2026-02-20'))->toBe(0.0);
});

it('offers nothing for a leave type that does not carry over', function () {
    $sick = LeaveType::factory()->for($this->tenant)->create([
        'auto_calculate' => false,
        'fixed_days_per_year' => 10,
        'allows_carryover' => false,
    ]);

    LeaveRequest::factory()->for($this->employee)->for($sick)->approved()->create([
        'start_date' => '2025-06-01', 'end_date' => '2025-06-10',
        'days_count' => 2, 'days_from_carryover' => 0,
    ]);

    expect($this->service->carryoverAvailable($this->employee, $sick, '2026-02-20'))->toBe(0.0);
});

it('spends carried-over days before the current year\'s', function () {
    useDaysIn2025($this->employee, $this->annual);

    // A 2-day request fits entirely inside the 3 carried-over days.
    expect($this->service->allocateFromCarryover($this->employee, $this->annual, '2026-02-20', 2))->toBe(2.0);
});

it('splits a request across both years when the carry-over runs out', function () {
    useDaysIn2025($this->employee, $this->annual);

    // 3 carried-over days left, 5 requested → 3 from last year, 2 from this one.
    $fromCarryover = $this->service->allocateFromCarryover($this->employee, $this->annual, '2026-02-20', 5);
    expect($fromCarryover)->toBe(3.0);

    LeaveRequest::factory()->for($this->employee)->for($this->annual)->approved()->create([
        'start_date' => '2026-02-20', 'end_date' => '2026-02-26',
        'days_count' => 5, 'days_from_carryover' => $fromCarryover,
    ]);

    // 2025 is now fully spent (7 used there + 3 drawn back into 2026)...
    expect($this->service->usedDays($this->employee, $this->annual, 2025))->toBe(10.0)
        ->and($this->service->remainingDays($this->employee, $this->annual, 2025))->toBe(0.0);

    // ...and only the 2 non-carryover days count against 2026.
    expect($this->service->usedDays($this->employee, $this->annual, 2026))->toBe(2.0)
        ->and($this->service->remainingDays($this->employee, $this->annual, 2026))->toBe(8.0);

    expect($this->service->availableFor($this->employee, $this->annual, '2026-03-01'))->toBe(8.0);
});

it('reports carried-over days separately in the dashboard summary', function () {
    useDaysIn2025($this->employee, $this->annual);

    $summary = $this->service->summaryForUser($this->employee, 2026)
        ->firstWhere('leaveType.id', $this->annual->id);

    expect($summary->carryoverRemaining)->toBe(3.0)
        ->and($summary->carryoverEntitled)->toBe(10.0)
        ->and($summary->carryoverExpiresAt->toDateString())->toBe('2026-03-31')
        ->and($summary->entitled)->toBe(10.0)
        ->and($summary->remaining)->toBe(10.0);
});

it('hides carried-over days from the summary once the deadline passes', function () {
    useDaysIn2025($this->employee, $this->annual);
    Carbon::setTestNow('2026-04-01');

    $summary = $this->service->summaryForUser($this->employee, 2026)
        ->firstWhere('leaveType.id', $this->annual->id);

    expect($summary->carryoverRemaining)->toBe(0.0)
        ->and($summary->carryoverExpiresAt)->toBeNull();
});

it('clamps a deadline day that the month does not have', function () {
    $this->tenant->update(['carryover_deadline_month' => 2, 'carryover_deadline_day' => 31]);

    // February 2026 has 28 days, so the deadline lands on the 28th.
    expect($this->tenant->carryoverDeadlineFor(2026)->toDateString())->toBe('2026-02-28');
});

it('lets an employee submit a request that only fits thanks to carried-over days', function () {
    useDaysIn2025($this->employee, $this->annual);

    actingInTenant($this->employee);

    Livewire::test(CreateLeaveRequest::class)
        ->set('data.leave_type_id', $this->annual->id)
        ->set('data.start_date', '2026-02-02')
        ->set('data.end_date', '2026-02-20')
        ->set('data.days_count', 12) // more than 2026's own 10 days, but 13 are available
        ->call('create');

    $request = LeaveRequest::where('user_id', $this->employee->id)
        ->where('start_date', '2026-02-02')
        ->first();

    expect($request)->not->toBeNull()
        ->and((float) $request->days_from_carryover)->toBe(3.0);
});

it('recomputes the carry-over split at approval, not at submission', function () {
    useDaysIn2025($this->employee, $this->annual);
    $admin = User::factory()->for($this->tenant)->admin()->create();

    // Both requests are provisionally allocated the same 3 carried-over days,
    // because a pending request does not consume anything yet.
    $first = LeaveRequest::factory()->for($this->employee)->for($this->annual)->create([
        'start_date' => '2026-02-02', 'end_date' => '2026-02-04',
        'days_count' => 3, 'days_from_carryover' => 3, 'status' => LeaveRequest::STATUS_PENDING,
    ]);
    $second = LeaveRequest::factory()->for($this->employee)->for($this->annual)->create([
        'start_date' => '2026-02-09', 'end_date' => '2026-02-11',
        'days_count' => 3, 'days_from_carryover' => 3, 'status' => LeaveRequest::STATUS_PENDING,
    ]);

    actingInTenant($admin);
    Livewire::test(ListLeaveRequests::class)->callTableAction('approve', $first);
    Livewire::test(ListLeaveRequests::class)->callTableAction('approve', $second);

    // The first took the 3 carried-over days; by the time the second was
    // approved there were none left, so it must fall back to 2026's own days.
    expect((float) $first->fresh()->days_from_carryover)->toBe(3.0)
        ->and((float) $second->fresh()->days_from_carryover)->toBe(0.0);

    expect($this->service->remainingDays($this->employee, $this->annual, 2025))->toBe(0.0)
        ->and($this->service->remainingDays($this->employee, $this->annual, 2026))->toBe(7.0);
});
