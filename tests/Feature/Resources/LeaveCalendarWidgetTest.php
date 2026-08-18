<?php

use App\Filament\Widgets\LeaveCalendar;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Tenant;
use App\Models\User;

function calendarInfo(): array
{
    return [
        'start' => now()->startOfMonth()->toDateString(),
        'end' => now()->endOfMonth()->toDateString(),
    ];
}

it('only shows the current employee\'s own leave requests', function () {
    $tenant = Tenant::factory()->create();
    $employee = User::factory()->for($tenant)->create();
    $otherEmployee = User::factory()->for($tenant)->create();
    $leaveType = LeaveType::factory()->for($tenant)->create();

    LeaveRequest::factory()->for($employee)->for($leaveType)->approved()->create([
        'start_date' => now()->addDays(2),
        'end_date' => now()->addDays(4),
    ]);
    LeaveRequest::factory()->for($otherEmployee)->for($leaveType)->approved()->create([
        'start_date' => now()->addDays(2),
        'end_date' => now()->addDays(4),
    ]);

    actingInTenant($employee);

    $events = (new LeaveCalendar)->fetchEvents(calendarInfo());

    expect($events)->toHaveCount(1);
});

it('shows every employee\'s leave requests to an admin', function () {
    $tenant = Tenant::factory()->create();
    $admin = User::factory()->for($tenant)->admin()->create();
    $employeeA = User::factory()->for($tenant)->create();
    $employeeB = User::factory()->for($tenant)->create();
    $leaveType = LeaveType::factory()->for($tenant)->create();

    LeaveRequest::factory()->for($employeeA)->for($leaveType)->approved()->create([
        'start_date' => now()->addDays(2),
        'end_date' => now()->addDays(4),
    ]);
    LeaveRequest::factory()->for($employeeB)->for($leaveType)->approved()->create([
        'start_date' => now()->addDays(5),
        'end_date' => now()->addDays(6),
    ]);

    actingInTenant($admin);

    $events = (new LeaveCalendar)->fetchEvents(calendarInfo());

    expect($events)->toHaveCount(2);
});

it('excludes rejected and cancelled leave requests from the calendar', function () {
    $tenant = Tenant::factory()->create();
    $employee = User::factory()->for($tenant)->create();
    $leaveType = LeaveType::factory()->for($tenant)->create();

    LeaveRequest::factory()->for($employee)->for($leaveType)->rejected()->create([
        'start_date' => now()->addDays(2),
        'end_date' => now()->addDays(4),
    ]);

    actingInTenant($employee);

    $events = (new LeaveCalendar)->fetchEvents(calendarInfo());

    expect($events)->toHaveCount(0);
});
