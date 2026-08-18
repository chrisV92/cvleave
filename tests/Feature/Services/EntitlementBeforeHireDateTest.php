<?php

use App\Models\LeaveType;
use App\Models\Tenant;
use App\Models\User;
use App\Services\LeaveBalanceService;
use Illuminate\Support\Carbon;

beforeEach(function () {
    Carbon::setTestNow('2026-08-18');
    $this->service = new LeaveBalanceService;
    $this->tenant = Tenant::factory()->create();
});

afterEach(function () {
    Carbon::setTestNow();
});

/**
 * The Greek-law formula already refuses years before the hire date, but the
 * fixed-days and tiered paths used to ignore hire_date entirely and hand a
 * brand-new employee a full entitlement for a year they were not here.
 */
it('gives no entitlement for a year that ended before the employee joined', function (array $typeAttributes, array $rules) {
    $leaveType = LeaveType::factory()->for($this->tenant)->create($typeAttributes);

    if ($rules) {
        $leaveType->accrualRules()->createMany($rules);
    }

    $employee = User::factory()->for($this->tenant)->create(['hire_date' => '2026-03-01']);

    expect($this->service->entitledDays($employee, $leaveType, 2025))->toBe(0.0);
})->with([
    'greek law' => [['auto_calculate' => true, 'use_greek_law_formula' => true, 'fixed_days_per_year' => null], []],
    'fixed days' => [['auto_calculate' => false, 'use_greek_law_formula' => false, 'fixed_days_per_year' => 20], []],
    'tiered' => [
        ['auto_calculate' => true, 'use_greek_law_formula' => false, 'fixed_days_per_year' => null],
        [['min_years_service' => 0, 'max_years_service' => null, 'days_per_year' => 18]],
    ],
]);

it('still prorates the joining year under Greek law', function () {
    $leaveType = LeaveType::factory()->for($this->tenant)->greekLaw()->create();
    $employee = User::factory()->for($this->tenant)->create(['hire_date' => '2025-11-01']);

    // Two completed months in 2025 at two days each.
    expect($this->service->entitledDays($employee, $leaveType, 2025))->toBe(2.0);
});

it('lets an explicit manual override win even for a pre-hire year', function () {
    $leaveType = LeaveType::factory()->for($this->tenant)->create([
        'auto_calculate' => false, 'fixed_days_per_year' => 20,
    ]);
    $employee = User::factory()->for($this->tenant)->create(['hire_date' => '2026-03-01']);

    $employee->leaveBalances()->create([
        'leave_type_id' => $leaveType->id,
        'year' => 2025,
        'manual_override_days' => 4,
    ]);

    expect($this->service->entitledDays($employee, $leaveType, 2025))->toBe(4.0);
});

it('reports the same zero entitlement through the dashboard summary', function () {
    $leaveType = LeaveType::factory()->for($this->tenant)->create([
        'auto_calculate' => false, 'fixed_days_per_year' => 20, 'is_active' => true,
    ]);
    $employee = User::factory()->for($this->tenant)->create(['hire_date' => '2026-03-01']);

    $summary = $this->service->summaryForUser($employee, 2025)
        ->firstWhere('leaveType.id', $leaveType->id);

    expect($summary->entitled)->toBe(0.0)
        ->and($summary->remaining)->toBe(0.0);
});
