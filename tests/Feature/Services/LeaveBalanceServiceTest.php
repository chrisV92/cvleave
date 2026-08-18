<?php

use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Tenant;
use App\Models\User;
use App\Services\LeaveBalanceService;

beforeEach(function () {
    $this->service = new LeaveBalanceService;
});

it('uses the manual override when one is set, ignoring auto-calculation entirely', function () {
    $user = User::factory()->create(['hire_date' => now()->subYears(20)]);
    $leaveType = LeaveType::factory()->greekLaw()->create();

    $user->leaveBalances()->create([
        'leave_type_id' => $leaveType->id,
        'year' => now()->year,
        'manual_override_days' => 5,
    ]);

    expect($this->service->entitledDays($user, $leaveType, now()->year))->toBe(5.0);
});

it('falls back to the fixed_days_per_year when auto_calculate is off', function () {
    $user = User::factory()->create();
    $leaveType = LeaveType::factory()->create(['auto_calculate' => false, 'fixed_days_per_year' => 15]);

    expect($this->service->entitledDays($user, $leaveType, now()->year))->toBe(15.0);
});

it('uses accrual rule tiers when auto_calculate is on without the Greek law formula', function () {
    $user = User::factory()->create(['hire_date' => now()->subYears(5)]);
    $leaveType = LeaveType::factory()->create(['auto_calculate' => true, 'use_greek_law_formula' => false]);

    $leaveType->accrualRules()->createMany([
        ['min_years_service' => 0, 'max_years_service' => 2, 'days_per_year' => 18],
        ['min_years_service' => 3, 'max_years_service' => null, 'days_per_year' => 24],
    ]);

    expect($this->service->entitledDays($user, $leaveType, now()->year))->toBe(24.0);
});

it('uses the Greek law formula when use_greek_law_formula is on', function () {
    $user = User::factory()->create(['hire_date' => now()->subYears(3)]);
    $leaveType = LeaveType::factory()->greekLaw()->create();

    expect($this->service->entitledDays($user, $leaveType, now()->year))->toBe(22.0);
});

it('sums only approved leave requests within the given year as used days', function () {
    $user = User::factory()->create();
    $leaveType = LeaveType::factory()->create();

    LeaveRequest::factory()->for($user)->for($leaveType)->approved()->create([
        'start_date' => now()->startOfYear()->addDays(10),
        'end_date' => now()->startOfYear()->addDays(12),
        'days_count' => 3,
    ]);
    LeaveRequest::factory()->for($user)->for($leaveType)->create([ // still pending
        'start_date' => now()->startOfYear()->addDays(20),
        'end_date' => now()->startOfYear()->addDays(22),
        'days_count' => 3,
    ]);
    LeaveRequest::factory()->for($user)->for($leaveType)->rejected()->create([
        'start_date' => now()->startOfYear()->addDays(30),
        'end_date' => now()->startOfYear()->addDays(32),
        'days_count' => 3,
    ]);

    expect($this->service->usedDays($user, $leaveType, now()->year))->toBe(3.0);
});

it('computes remaining days as entitled minus used', function () {
    $user = User::factory()->create();
    $leaveType = LeaveType::factory()->create(['auto_calculate' => false, 'fixed_days_per_year' => 20]);

    LeaveRequest::factory()->for($user)->for($leaveType)->approved()->create([
        'start_date' => now()->startOfYear()->addDays(10),
        'end_date' => now()->startOfYear()->addDays(15),
        'days_count' => 6,
    ]);

    expect($this->service->remainingDays($user, $leaveType, now()->year))->toBe(14.0);
});

it('detects an overlap when a new range shares even a single boundary day', function () {
    $user = User::factory()->create();
    $leaveType = LeaveType::factory()->create();

    LeaveRequest::factory()->for($user)->for($leaveType)->approved()->create([
        'start_date' => '2026-08-21',
        'end_date' => '2026-09-06',
    ]);

    expect($this->service->hasOverlap($user, '2026-09-06', '2026-09-17'))->toBeTrue()
        ->and($this->service->hasOverlap($user, '2026-09-07', '2026-09-17'))->toBeFalse();
});

it('excludes the given leave request id from the overlap check (for edits)', function () {
    $user = User::factory()->create();
    $leaveType = LeaveType::factory()->create();

    $existing = LeaveRequest::factory()->for($user)->for($leaveType)->create([
        'start_date' => '2026-08-21',
        'end_date' => '2026-09-06',
    ]);

    expect($this->service->hasOverlap($user, '2026-08-21', '2026-09-06', excludeLeaveRequestId: $existing->id))->toBeFalse();
});

it('ignores rejected and cancelled requests when checking overlap', function () {
    $user = User::factory()->create();
    $leaveType = LeaveType::factory()->create();

    LeaveRequest::factory()->for($user)->for($leaveType)->rejected()->create([
        'start_date' => '2026-08-21',
        'end_date' => '2026-09-06',
    ]);

    expect($this->service->hasOverlap($user, '2026-08-21', '2026-09-06'))->toBeFalse();
});

it('counts business days inclusively, excluding weekends', function () {
    // Monday 2026-08-17 to Friday 2026-08-21: 5 business days.
    expect($this->service->countBusinessDays(
        new DateTime('2026-08-17'),
        new DateTime('2026-08-21')
    ))->toBe(5);

    // Monday 2026-08-17 to Sunday 2026-08-23: still 5 business days.
    expect($this->service->countBusinessDays(
        new DateTime('2026-08-17'),
        new DateTime('2026-08-23')
    ))->toBe(5);

    // A single Saturday: 0 business days.
    expect($this->service->countBusinessDays(
        new DateTime('2026-08-22'),
        new DateTime('2026-08-22')
    ))->toBe(0);
});

it('only summarizes leave types belonging to the user\'s own tenant', function () {
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();

    $user = User::factory()->for($tenantA)->create();
    LeaveType::factory()->for($tenantA)->create(['is_active' => true, 'name' => 'Tenant A Type']);
    LeaveType::factory()->for($tenantB)->create(['is_active' => true, 'name' => 'Tenant B Type']);

    $summary = $this->service->summaryForUser($user, now()->year);
    $names = $summary->pluck('leaveType.name');

    // Tenant::factory() auto-seeds 3 default leave types on creation, so tenant A
    // ends up with those plus the explicit one created above — never tenant B's.
    expect($names)->toContain('Tenant A Type')
        ->and($names)->not->toContain('Tenant B Type')
        ->and($summary->pluck('leaveType.tenant_id')->unique()->all())->toBe([$tenantA->id]);
});
