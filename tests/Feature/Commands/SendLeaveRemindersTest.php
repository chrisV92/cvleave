<?php

use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use App\Notifications\LeaveEndingSoon;
use App\Notifications\LeaveStartingSoon;
use Illuminate\Support\Facades\Notification;

it('notifies the employee and all admins for leaves starting tomorrow', function () {
    Notification::fake();

    $admin = User::factory()->admin()->create();
    $employee = User::factory()->create();
    $leaveType = LeaveType::factory()->create();

    $leaveRequest = LeaveRequest::factory()->for($employee)->for($leaveType)->approved()->create([
        'start_date' => now()->addDay(),
        'end_date' => now()->addDays(5),
    ]);

    $this->artisan('leave:send-reminders')->assertSuccessful();

    Notification::assertSentTo($employee, LeaveStartingSoon::class, fn ($n) => ! $n->forAdmin);
    Notification::assertSentTo($admin, LeaveStartingSoon::class, fn ($n) => $n->forAdmin);
});

it('notifies the employee and all admins for leaves ending tomorrow', function () {
    Notification::fake();

    $admin = User::factory()->admin()->create();
    $employee = User::factory()->create();
    $leaveType = LeaveType::factory()->create();

    LeaveRequest::factory()->for($employee)->for($leaveType)->approved()->create([
        'start_date' => now()->subDays(4),
        'end_date' => now()->addDay(),
    ]);

    $this->artisan('leave:send-reminders')->assertSuccessful();

    Notification::assertSentTo($employee, LeaveEndingSoon::class, fn ($n) => ! $n->forAdmin);
    Notification::assertSentTo($admin, LeaveEndingSoon::class, fn ($n) => $n->forAdmin);
});

it('does not notify for leaves that are still pending', function () {
    Notification::fake();

    $employee = User::factory()->create();
    $leaveType = LeaveType::factory()->create();

    LeaveRequest::factory()->for($employee)->for($leaveType)->create([ // pending, not approved
        'start_date' => now()->addDay(),
        'end_date' => now()->addDays(3),
    ]);

    $this->artisan('leave:send-reminders')->assertSuccessful();

    Notification::assertNotSentTo($employee, LeaveStartingSoon::class);
});

it('does not notify for leaves starting or ending further than tomorrow', function () {
    Notification::fake();

    $employee = User::factory()->create();
    $leaveType = LeaveType::factory()->create();

    LeaveRequest::factory()->for($employee)->for($leaveType)->approved()->create([
        'start_date' => now()->addDays(5),
        'end_date' => now()->addDays(8),
    ]);

    $this->artisan('leave:send-reminders')->assertSuccessful();

    Notification::assertNotSentTo($employee, LeaveStartingSoon::class);
    Notification::assertNotSentTo($employee, LeaveEndingSoon::class);
});
