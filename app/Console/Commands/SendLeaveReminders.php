<?php

namespace App\Console\Commands;

use App\Models\LeaveRequest;
use App\Models\User;
use App\Notifications\LeaveEndingSoon;
use App\Notifications\LeaveStartingSoon;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class SendLeaveReminders extends Command
{
    protected $signature = 'leave:send-reminders';

    protected $description = 'Notify employees and admins about leaves starting or ending tomorrow';

    public function handle(): void
    {
        $tomorrow = Carbon::tomorrow()->toDateString();
        $admins = User::where('role', User::ROLE_ADMIN)->get();

        $starting = LeaveRequest::query()
            ->where('status', LeaveRequest::STATUS_APPROVED)
            ->whereDate('start_date', $tomorrow)
            ->with(['user', 'leaveType'])
            ->get();

        foreach ($starting as $leaveRequest) {
            $leaveRequest->user->notify(new LeaveStartingSoon($leaveRequest));
            foreach ($admins as $admin) {
                $admin->notify(new LeaveStartingSoon($leaveRequest, forAdmin: true));
            }
        }

        $ending = LeaveRequest::query()
            ->where('status', LeaveRequest::STATUS_APPROVED)
            ->whereDate('end_date', $tomorrow)
            ->with(['user', 'leaveType'])
            ->get();

        foreach ($ending as $leaveRequest) {
            $leaveRequest->user->notify(new LeaveEndingSoon($leaveRequest));
            foreach ($admins as $admin) {
                $admin->notify(new LeaveEndingSoon($leaveRequest, forAdmin: true));
            }
        }

        $this->info("Sent reminders for {$starting->count()} starting and {$ending->count()} ending leaves.");
    }
}
