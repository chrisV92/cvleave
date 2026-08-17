<?php

use App\Filament\Resources\LeaveRequests\Pages\CreateLeaveRequest;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use App\Notifications\LeaveRequestReviewed;
use App\Notifications\LeaveRequestSubmitted;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

it('notifies every admin when a new leave request is submitted', function () {
    Notification::fake();

    $adminA = User::factory()->admin()->create();
    $adminB = User::factory()->admin()->create();
    $employee = User::factory()->create();
    $leaveType = LeaveType::factory()->create(['auto_calculate' => false, 'fixed_days_per_year' => 20]);

    $this->actingAs($employee);

    Livewire::test(CreateLeaveRequest::class)
        ->set('data.leave_type_id', $leaveType->id)
        ->set('data.start_date', '2026-11-02')
        ->set('data.end_date', '2026-11-04')
        ->set('data.days_count', 3)
        ->call('create');

    Notification::assertSentTo([$adminA, $adminB], LeaveRequestSubmitted::class);
    Notification::assertNotSentTo($employee, LeaveRequestSubmitted::class);
});

it('notifies the employee when their request is approved', function () {
    Notification::fake();

    $admin = User::factory()->admin()->create();
    $employee = User::factory()->create();
    $leaveType = LeaveType::factory()->create();
    $leaveRequest = LeaveRequest::factory()->for($employee)->for($leaveType)->create();

    $this->actingAs($admin);
    $leaveRequest->update(['status' => LeaveRequest::STATUS_APPROVED]);

    Notification::assertSentTo($employee, LeaveRequestReviewed::class, function ($notification) {
        return $notification->leaveRequest->status === LeaveRequest::STATUS_APPROVED;
    });
});

it('notifies the employee when their request is rejected', function () {
    Notification::fake();

    $admin = User::factory()->admin()->create();
    $employee = User::factory()->create();
    $leaveType = LeaveType::factory()->create();
    $leaveRequest = LeaveRequest::factory()->for($employee)->for($leaveType)->create();

    $this->actingAs($admin);
    $leaveRequest->update(['status' => LeaveRequest::STATUS_REJECTED, 'rejection_reason' => 'Understaffed that week']);

    Notification::assertSentTo($employee, LeaveRequestReviewed::class, function ($notification) {
        return $notification->leaveRequest->status === LeaveRequest::STATUS_REJECTED;
    });
});

it('does not notify the employee for unrelated field updates', function () {
    Notification::fake();

    $admin = User::factory()->admin()->create();
    $employee = User::factory()->create();
    $leaveType = LeaveType::factory()->create();
    $leaveRequest = LeaveRequest::factory()->for($employee)->for($leaveType)->create();

    $this->actingAs($admin);
    $leaveRequest->update(['days_count' => 7]);

    Notification::assertNotSentTo($employee, LeaveRequestReviewed::class);
});
