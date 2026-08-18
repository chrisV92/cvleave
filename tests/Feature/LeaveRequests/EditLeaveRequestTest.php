<?php

use App\Filament\Resources\LeaveRequests\LeaveRequestResource;
use App\Filament\Resources\LeaveRequests\Pages\EditLeaveRequest;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Tenant;
use App\Models\User;
use Livewire\Livewire;

it('allows an employee to edit their own pending request', function () {
    $tenant = Tenant::factory()->create();
    $employee = User::factory()->for($tenant)->create();
    $leaveType = LeaveType::factory()->for($tenant)->create();
    $leaveRequest = LeaveRequest::factory()->for($employee)->for($leaveType)->create();

    actingInTenant($employee);

    expect(LeaveRequestResource::canEdit($leaveRequest))->toBeTrue();
});

it('forbids an employee from editing their own approved request', function () {
    $tenant = Tenant::factory()->create();
    $employee = User::factory()->for($tenant)->create();
    $leaveType = LeaveType::factory()->for($tenant)->create();
    $leaveRequest = LeaveRequest::factory()->for($employee)->for($leaveType)->approved()->create();

    actingInTenant($employee);

    expect(LeaveRequestResource::canEdit($leaveRequest))->toBeFalse();
});

it('forbids an employee from editing someone else\'s request', function () {
    $tenant = Tenant::factory()->create();
    $employee = User::factory()->for($tenant)->create();
    $otherEmployee = User::factory()->for($tenant)->create();
    $leaveType = LeaveType::factory()->for($tenant)->create();
    $leaveRequest = LeaveRequest::factory()->for($otherEmployee)->for($leaveType)->create();

    actingInTenant($employee);

    expect(LeaveRequestResource::canEdit($leaveRequest))->toBeFalse();
});

it('allows an admin to edit any request regardless of status or owner', function () {
    $tenant = Tenant::factory()->create();
    $admin = User::factory()->for($tenant)->admin()->create();
    $employee = User::factory()->for($tenant)->create();
    $leaveType = LeaveType::factory()->for($tenant)->create();
    $leaveRequest = LeaveRequest::factory()->for($employee)->for($leaveType)->approved()->create();

    actingInTenant($admin);

    expect(LeaveRequestResource::canEdit($leaveRequest))->toBeTrue();
});

it('scopes the resource query to only the current user\'s requests for non-admins', function () {
    $tenant = Tenant::factory()->create();
    $employee = User::factory()->for($tenant)->create();
    $otherEmployee = User::factory()->for($tenant)->create();
    $leaveType = LeaveType::factory()->for($tenant)->create();

    LeaveRequest::factory()->for($employee)->for($leaveType)->create();
    LeaveRequest::factory()->for($otherEmployee)->for($leaveType)->create();

    actingInTenant($employee);

    expect(LeaveRequestResource::getEloquentQuery()->count())->toBe(1);
});

it('lets an admin see every request in the resource query', function () {
    $tenant = Tenant::factory()->create();
    $admin = User::factory()->for($tenant)->admin()->create();
    $employeeA = User::factory()->for($tenant)->create();
    $employeeB = User::factory()->for($tenant)->create();
    $leaveType = LeaveType::factory()->for($tenant)->create();

    LeaveRequest::factory()->for($employeeA)->for($leaveType)->create();
    LeaveRequest::factory()->for($employeeB)->for($leaveType)->create();

    actingInTenant($admin);

    expect(LeaveRequestResource::getEloquentQuery()->count())->toBe(2);
});

// The end_date field is hidden for half-day/hours requests, so it can be absent
// from the form state entirely — the edit page must fall back to start_date the
// same way the create page does, instead of blowing up on a missing array key.
it('lets an employee edit their own hours-based request', function () {
    $tenant = Tenant::factory()->create();
    $employee = User::factory()->for($tenant)->create();
    $leaveType = LeaveType::factory()->for($tenant)->create([
        'auto_calculate' => false,
        'fixed_days_per_year' => 20,
    ]);

    $leaveRequest = LeaveRequest::factory()->for($employee)->for($leaveType)->create([
        'duration_type' => LeaveRequest::DURATION_HOURS,
        'start_date' => '2026-11-02',
        'end_date' => '2026-11-02',
        'hours' => 3,
        'days_count' => 0.375,
        'status' => LeaveRequest::STATUS_PENDING,
    ]);

    actingInTenant($employee);

    Livewire::test(EditLeaveRequest::class, ['record' => $leaveRequest->getKey()])
        ->set('data.hours', 4)
        ->call('save');

    $fresh = $leaveRequest->fresh();

    expect((float) $fresh->hours)->toBe(4.0)
        ->and((float) $fresh->days_count)->toBe(0.5)
        ->and($fresh->end_date->toDateString())->toBe('2026-11-02');
});

it('lets an employee edit their own half-day request', function () {
    $tenant = Tenant::factory()->create();
    $employee = User::factory()->for($tenant)->create();
    $leaveType = LeaveType::factory()->for($tenant)->create([
        'auto_calculate' => false,
        'fixed_days_per_year' => 20,
    ]);

    $leaveRequest = LeaveRequest::factory()->for($employee)->for($leaveType)->create([
        'duration_type' => LeaveRequest::DURATION_HALF_DAY,
        'start_date' => '2026-11-02',
        'end_date' => '2026-11-02',
        'days_count' => 0.5,
        'status' => LeaveRequest::STATUS_PENDING,
    ]);

    actingInTenant($employee);

    Livewire::test(EditLeaveRequest::class, ['record' => $leaveRequest->getKey()])
        ->set('data.start_date', '2026-11-03')
        ->call('save');

    $fresh = $leaveRequest->fresh();

    expect($fresh->start_date->toDateString())->toBe('2026-11-03')
        ->and($fresh->end_date->toDateString())->toBe('2026-11-03')
        ->and((float) $fresh->days_count)->toBe(0.5);
});
