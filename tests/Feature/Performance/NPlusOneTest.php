<?php

use App\Filament\Resources\LeaveRequests\Pages\ListLeaveRequests;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use App\Services\LeaveBalanceService;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

it('does not grow the query count as the number of leave requests grows (admin list view)', function () {
    $admin = User::factory()->admin()->create();
    $leaveType = LeaveType::factory()->create();

    LeaveRequest::factory()->count(3)->for($leaveType)->recycle(User::factory()->count(3)->create())->create();

    $this->actingAs($admin);

    DB::enableQueryLog();
    Livewire::test(ListLeaveRequests::class);
    $queriesFor3 = count(DB::getQueryLog());
    DB::disableQueryLog();
    DB::flushQueryLog();

    LeaveRequest::factory()->count(15)->for($leaveType)->recycle(User::factory()->count(5)->create())->create();

    DB::enableQueryLog();
    Livewire::test(ListLeaveRequests::class);
    $queriesFor18 = count(DB::getQueryLog());
    DB::disableQueryLog();

    // If this were N+1, going from 3 to 18 records would roughly 6x the query count.
    // With eager loading, it should stay essentially flat (allow a small margin).
    expect($queriesFor18)->toBeLessThan($queriesFor3 + 5);
});

it('runs the dashboard leave-balance summary in a constant number of queries regardless of leave type count', function () {
    $user = User::factory()->create(['hire_date' => now()->subYears(3)]);
    LeaveType::factory()->count(2)->create(['is_active' => true]);

    $service = app(LeaveBalanceService::class);

    DB::enableQueryLog();
    $service->summaryForUser($user, now()->year);
    $queriesFor2Types = count(DB::getQueryLog());
    DB::disableQueryLog();
    DB::flushQueryLog();

    LeaveType::factory()->count(8)->create(['is_active' => true]);

    DB::enableQueryLog();
    $service->summaryForUser($user, now()->year);
    $queriesFor10Types = count(DB::getQueryLog());
    DB::disableQueryLog();

    expect($queriesFor10Types)->toBe($queriesFor2Types);
});

it('fetches all calendar events with a constant number of queries regardless of leave request count', function () {
    $admin = User::factory()->admin()->create();
    $leaveType = LeaveType::factory()->create();

    LeaveRequest::factory()->count(3)->for($leaveType)->approved()->recycle(User::factory()->count(3)->create())->create([
        'start_date' => now()->addDays(2),
        'end_date' => now()->addDays(4),
    ]);

    $this->actingAs($admin);
    $widget = new \App\Filament\Widgets\LeaveCalendar();
    $info = ['start' => now()->startOfMonth()->toDateString(), 'end' => now()->endOfMonth()->toDateString()];

    DB::enableQueryLog();
    $widget->fetchEvents($info);
    $queriesFor3 = count(DB::getQueryLog());
    DB::disableQueryLog();
    DB::flushQueryLog();

    LeaveRequest::factory()->count(15)->for($leaveType)->approved()->recycle(User::factory()->count(5)->create())->create([
        'start_date' => now()->addDays(2),
        'end_date' => now()->addDays(4),
    ]);

    DB::enableQueryLog();
    $widget->fetchEvents($info);
    $queriesFor18 = count(DB::getQueryLog());
    DB::disableQueryLog();

    expect($queriesFor18)->toBe($queriesFor3);
});
