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
