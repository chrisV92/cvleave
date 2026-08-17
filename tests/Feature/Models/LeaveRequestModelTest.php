<?php

use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;

it('automatically stamps reviewed_by and reviewed_at when status changes to approved', function () {
    $admin = User::factory()->admin()->create();
    $employee = User::factory()->create();
    $leaveType = LeaveType::factory()->create();
    $leaveRequest = LeaveRequest::factory()->for($employee)->for($leaveType)->create();

    $this->actingAs($admin);

    $leaveRequest->update(['status' => LeaveRequest::STATUS_APPROVED]);

    expect($leaveRequest->reviewed_by)->toBe($admin->id)
        ->and($leaveRequest->reviewed_at)->not->toBeNull();
});

it('automatically stamps reviewed_by and reviewed_at when status changes to rejected', function () {
    $admin = User::factory()->admin()->create();
    $employee = User::factory()->create();
    $leaveType = LeaveType::factory()->create();
    $leaveRequest = LeaveRequest::factory()->for($employee)->for($leaveType)->create();

    $this->actingAs($admin);

    $leaveRequest->update(['status' => LeaveRequest::STATUS_REJECTED, 'rejection_reason' => 'No coverage available']);

    expect($leaveRequest->reviewed_by)->toBe($admin->id)
        ->and($leaveRequest->reviewed_at)->not->toBeNull();
});

it('does not stamp reviewed_by when saved without a status change', function () {
    $admin = User::factory()->admin()->create();
    $employee = User::factory()->create();
    $leaveType = LeaveType::factory()->create();
    $leaveRequest = LeaveRequest::factory()->for($employee)->for($leaveType)->create();

    $this->actingAs($admin);

    $leaveRequest->update(['days_count' => 5]);

    expect($leaveRequest->reviewed_by)->toBeNull()
        ->and($leaveRequest->reviewed_at)->toBeNull();
});
