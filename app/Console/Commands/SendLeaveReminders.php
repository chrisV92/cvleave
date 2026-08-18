<?php

namespace App\Console\Commands;

use App\Models\LeaveRequest;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\LeaveEndingSoon;
use App\Notifications\LeaveStartingSoon;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Spatie\Permission\PermissionRegistrar;

class SendLeaveReminders extends Command
{
    protected $signature = 'leave:send-reminders';

    protected $description = 'Notify employees and admins about leaves starting or ending tomorrow';

    public function handle(): void
    {
        $tomorrow = Carbon::tomorrow()->toDateString();
        $startingCount = 0;
        $endingCount = 0;

        Tenant::all()->each(function (Tenant $tenant) use ($tomorrow, &$startingCount, &$endingCount) {
            app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);
            $admins = User::role('admin')->where('tenant_id', $tenant->id)->get();

            $starting = LeaveRequest::query()
                ->where('status', LeaveRequest::STATUS_APPROVED)
                ->whereDate('start_date', $tomorrow)
                ->whereHas('user', fn ($query) => $query->where('tenant_id', $tenant->id))
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
                ->whereHas('user', fn ($query) => $query->where('tenant_id', $tenant->id))
                ->with(['user', 'leaveType'])
                ->get();

            foreach ($ending as $leaveRequest) {
                $leaveRequest->user->notify(new LeaveEndingSoon($leaveRequest));
                foreach ($admins as $admin) {
                    $admin->notify(new LeaveEndingSoon($leaveRequest, forAdmin: true));
                }
            }

            $startingCount += $starting->count();
            $endingCount += $ending->count();
        });

        $this->info("Sent reminders for {$startingCount} starting and {$endingCount} ending leaves.");
    }
}
