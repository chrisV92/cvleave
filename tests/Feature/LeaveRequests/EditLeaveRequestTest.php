<?php

use App\Filament\Resources\LeaveRequests\LeaveRequestResource;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Tenant;
use App\Models\User;

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
