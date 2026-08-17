<?php

use App\Filament\Resources\LeaveRequests\LeaveRequestResource;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;

it('allows an employee to edit their own pending request', function () {
    $employee = User::factory()->create();
    $leaveType = LeaveType::factory()->create();
    $leaveRequest = LeaveRequest::factory()->for($employee)->for($leaveType)->create();

    $this->actingAs($employee);

    expect(LeaveRequestResource::canEdit($leaveRequest))->toBeTrue();
});

it('forbids an employee from editing their own approved request', function () {
    $employee = User::factory()->create();
    $leaveType = LeaveType::factory()->create();
    $leaveRequest = LeaveRequest::factory()->for($employee)->for($leaveType)->approved()->create();

    $this->actingAs($employee);

    expect(LeaveRequestResource::canEdit($leaveRequest))->toBeFalse();
});

it('forbids an employee from editing someone else\'s request', function () {
    $employee = User::factory()->create();
    $otherEmployee = User::factory()->create();
    $leaveType = LeaveType::factory()->create();
    $leaveRequest = LeaveRequest::factory()->for($otherEmployee)->for($leaveType)->create();

    $this->actingAs($employee);

    expect(LeaveRequestResource::canEdit($leaveRequest))->toBeFalse();
});

it('allows an admin to edit any request regardless of status or owner', function () {
    $admin = User::factory()->admin()->create();
    $employee = User::factory()->create();
    $leaveType = LeaveType::factory()->create();
    $leaveRequest = LeaveRequest::factory()->for($employee)->for($leaveType)->approved()->create();

    $this->actingAs($admin);

    expect(LeaveRequestResource::canEdit($leaveRequest))->toBeTrue();
});

it('scopes the resource query to only the current user\'s requests for non-admins', function () {
    $employee = User::factory()->create();
    $otherEmployee = User::factory()->create();
    $leaveType = LeaveType::factory()->create();

    LeaveRequest::factory()->for($employee)->for($leaveType)->create();
    LeaveRequest::factory()->for($otherEmployee)->for($leaveType)->create();

    $this->actingAs($employee);

    expect(LeaveRequestResource::getEloquentQuery()->count())->toBe(1);
});

it('lets an admin see every request in the resource query', function () {
    $admin = User::factory()->admin()->create();
    $employeeA = User::factory()->create();
    $employeeB = User::factory()->create();
    $leaveType = LeaveType::factory()->create();

    LeaveRequest::factory()->for($employeeA)->for($leaveType)->create();
    LeaveRequest::factory()->for($employeeB)->for($leaveType)->create();

    $this->actingAs($admin);

    expect(LeaveRequestResource::getEloquentQuery()->count())->toBe(2);
});
