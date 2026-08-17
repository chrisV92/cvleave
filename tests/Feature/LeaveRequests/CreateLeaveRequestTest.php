<?php

use App\Filament\Resources\LeaveRequests\Pages\CreateLeaveRequest;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use Livewire\Livewire;

function submitLeaveRequest(User $as, array $data)
{
    test()->actingAs($as);

    $test = Livewire::test(CreateLeaveRequest::class);

    foreach ($data as $key => $value) {
        $test->set("data.$key", $value);
    }

    return $test->call('create');
}

it('lets an employee create a valid leave request for themselves', function () {
    $employee = User::factory()->create(['hire_date' => now()->subYears(3)]);
    $leaveType = LeaveType::factory()->create(['auto_calculate' => false, 'fixed_days_per_year' => 20]);

    submitLeaveRequest($employee, [
        'leave_type_id' => $leaveType->id,
        'start_date' => '2026-11-02',
        'end_date' => '2026-11-04',
        'days_count' => 3,
    ]);

    $leaveRequest = LeaveRequest::first();

    expect($leaveRequest)->not->toBeNull()
        ->and($leaveRequest->user_id)->toBe($employee->id)
        ->and($leaveRequest->status)->toBe(LeaveRequest::STATUS_PENDING);
});

it('forces status to pending and user_id to self for a non-admin, even if tampered with', function () {
    $employee = User::factory()->create();
    $otherUser = User::factory()->create();
    $leaveType = LeaveType::factory()->create(['auto_calculate' => false, 'fixed_days_per_year' => 20]);

    test()->actingAs($employee);

    $test = Livewire::test(CreateLeaveRequest::class);
    $test->set('data.leave_type_id', $leaveType->id);
    $test->set('data.start_date', '2026-11-02');
    $test->set('data.end_date', '2026-11-04');
    $test->set('data.days_count', 3);
    $test->set('data.user_id', $otherUser->id);
    $test->set('data.status', LeaveRequest::STATUS_APPROVED);
    $test->call('create');

    $leaveRequest = LeaveRequest::first();

    expect($leaveRequest->user_id)->toBe($employee->id)
        ->and($leaveRequest->status)->toBe(LeaveRequest::STATUS_PENDING);
});

it('blocks a request that overlaps an existing pending or approved leave', function () {
    $employee = User::factory()->create();
    $leaveType = LeaveType::factory()->create(['auto_calculate' => false, 'fixed_days_per_year' => 30]);

    LeaveRequest::factory()->for($employee)->for($leaveType)->approved()->create([
        'start_date' => '2026-11-01',
        'end_date' => '2026-11-05',
        'days_count' => 3,
    ]);

    submitLeaveRequest($employee, [
        'leave_type_id' => $leaveType->id,
        'start_date' => '2026-11-05',
        'end_date' => '2026-11-07',
        'days_count' => 3,
    ]);

    expect(LeaveRequest::count())->toBe(1);
});

it('allows a request starting the day after a previous leave ends', function () {
    $employee = User::factory()->create();
    $leaveType = LeaveType::factory()->create(['auto_calculate' => false, 'fixed_days_per_year' => 30]);

    LeaveRequest::factory()->for($employee)->for($leaveType)->approved()->create([
        'start_date' => '2026-11-01',
        'end_date' => '2026-11-05',
        'days_count' => 3,
    ]);

    submitLeaveRequest($employee, [
        'leave_type_id' => $leaveType->id,
        'start_date' => '2026-11-06',
        'end_date' => '2026-11-07',
        'days_count' => 2,
    ]);

    expect(LeaveRequest::count())->toBe(2);
});

it('blocks a request that exceeds the remaining day balance', function () {
    $employee = User::factory()->create();
    $leaveType = LeaveType::factory()->create(['auto_calculate' => false, 'fixed_days_per_year' => 5]);

    submitLeaveRequest($employee, [
        'leave_type_id' => $leaveType->id,
        'start_date' => '2026-11-02',
        'end_date' => '2026-11-13', // 10 business days, more than the 5-day balance
        'days_count' => 10,
    ]);

    expect(LeaveRequest::count())->toBe(0);
});

it('allows a request that exactly matches the remaining day balance', function () {
    $employee = User::factory()->create();
    $leaveType = LeaveType::factory()->create(['auto_calculate' => false, 'fixed_days_per_year' => 3]);

    submitLeaveRequest($employee, [
        'leave_type_id' => $leaveType->id,
        'start_date' => '2026-11-02',
        'end_date' => '2026-11-04',
        'days_count' => 3,
    ]);

    expect(LeaveRequest::count())->toBe(1);
});

it('creates a half-day leave request with days_count 0.5', function () {
    $employee = User::factory()->create();
    $leaveType = LeaveType::factory()->create(['auto_calculate' => false, 'fixed_days_per_year' => 20]);

    submitLeaveRequest($employee, [
        'leave_type_id' => $leaveType->id,
        'duration_type' => LeaveRequest::DURATION_HALF_DAY,
        'start_date' => '2026-11-02',
        'end_date' => '2026-11-02',
        'days_count' => 0.5,
    ]);

    $leaveRequest = LeaveRequest::first();

    expect($leaveRequest->duration_type)->toBe(LeaveRequest::DURATION_HALF_DAY)
        ->and((float) $leaveRequest->days_count)->toBe(0.5);
});

it('creates an hours-based leave request with days_count as a fraction of an 8-hour day', function () {
    $employee = User::factory()->create();
    $leaveType = LeaveType::factory()->create(['auto_calculate' => false, 'fixed_days_per_year' => 20]);

    submitLeaveRequest($employee, [
        'leave_type_id' => $leaveType->id,
        'duration_type' => LeaveRequest::DURATION_HOURS,
        'start_date' => '2026-11-02',
        'end_date' => '2026-11-02',
        'hours' => 2,
        'days_count' => 0.25,
    ]);

    $leaveRequest = LeaveRequest::first();

    expect($leaveRequest->duration_type)->toBe(LeaveRequest::DURATION_HOURS)
        ->and((float) $leaveRequest->hours)->toBe(2.0)
        ->and((float) $leaveRequest->days_count)->toBe(0.25);
});

it('deducts an hours-based request correctly from the remaining balance', function () {
    $employee = User::factory()->create();
    $leaveType = LeaveType::factory()->create(['auto_calculate' => false, 'fixed_days_per_year' => 1]);

    // An already-approved 7-hour (0.875 day) request leaves 0.125 remaining —
    // a second 2-hour (0.25 day) request must be blocked.
    LeaveRequest::factory()->for($employee)->for($leaveType)->approved()->create([
        'duration_type' => LeaveRequest::DURATION_HOURS,
        'start_date' => '2026-11-02',
        'end_date' => '2026-11-02',
        'hours' => 7,
        'days_count' => 0.875,
    ]);

    submitLeaveRequest($employee, [
        'leave_type_id' => $leaveType->id,
        'duration_type' => LeaveRequest::DURATION_HOURS,
        'start_date' => '2026-11-03',
        'end_date' => '2026-11-03',
        'hours' => 2,
        'days_count' => 0.25,
    ]);

    expect(LeaveRequest::count())->toBe(1);
});

it('auto-computes days_count from hours via the live form fields, without it being set explicitly', function () {
    $employee = User::factory()->create();
    $leaveType = LeaveType::factory()->create(['auto_calculate' => false, 'fixed_days_per_year' => 20]);

    test()->actingAs($employee);

    Livewire::test(CreateLeaveRequest::class)
        ->set('data.leave_type_id', $leaveType->id)
        ->set('data.duration_type', LeaveRequest::DURATION_HOURS)
        ->set('data.start_date', '2026-11-02')
        ->set('data.hours', 3)
        ->call('create');

    $leaveRequest = LeaveRequest::first();

    expect($leaveRequest)->not->toBeNull()
        ->and($leaveRequest->end_date->toDateString())->toBe('2026-11-02')
        ->and((float) $leaveRequest->days_count)->toBe(0.375);
});

it('allows an admin to create a leave request on behalf of an employee with a chosen status', function () {
    $admin = User::factory()->admin()->create();
    $employee = User::factory()->create();
    $leaveType = LeaveType::factory()->create(['auto_calculate' => false, 'fixed_days_per_year' => 20]);

    submitLeaveRequest($admin, [
        'user_id' => $employee->id,
        'leave_type_id' => $leaveType->id,
        'start_date' => '2026-11-02',
        'end_date' => '2026-11-04',
        'days_count' => 3,
        'status' => LeaveRequest::STATUS_APPROVED,
    ]);

    $leaveRequest = LeaveRequest::first();

    expect($leaveRequest->user_id)->toBe($employee->id)
        ->and($leaveRequest->status)->toBe(LeaveRequest::STATUS_APPROVED);
});
