<?php

namespace App\Filament\Resources\LeaveRequests\Pages;

use App\Filament\Resources\LeaveRequests\LeaveRequestResource;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use App\Notifications\LeaveRequestSubmitted;
use App\Services\LeaveBalanceService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Carbon;

class CreateLeaveRequest extends CreateRecord
{
    protected static string $resource = LeaveRequestResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (! (auth()->user()?->isAdmin() ?? false)) {
            $data['user_id'] = auth()->id();
            $data['status'] = LeaveRequest::STATUS_PENDING;
        }

        $data['end_date'] ??= $data['start_date'];

        return $data;
    }

    protected function beforeCreate(): void
    {
        $data = $this->form->getState();
        $data['end_date'] ??= $data['start_date'];

        $user = User::find($data['user_id'] ?? auth()->id());
        $leaveType = LeaveType::find($data['leave_type_id']);
        $year = Carbon::parse($data['start_date'])->year;

        $service = app(LeaveBalanceService::class);

        if ($service->hasOverlap($user, $data['start_date'], $data['end_date'])) {
            Notification::make()
                ->title(__('Επικάλυψη με υπάρχουσα άδεια'))
                ->body(__('Ο/Η :name έχει ήδη άδεια (εκκρεμή ή εγκεκριμένη) που καλύπτει αυτό το διάστημα. Πρέπει να περάσει τουλάχιστον μία μέρα από τη λήξη προηγούμενης άδειας.', ['name' => $user->name]))
                ->danger()
                ->send();

            $this->halt();
        }

        $remaining = $service->remainingDays($user, $leaveType, $year);

        if ($data['days_count'] > $remaining) {
            Notification::make()
                ->title(__('Ανεπαρκές υπόλοιπο ημερών'))
                ->body(__('Ο/Η :name έχει μόνο :remaining διαθέσιμες μέρες για :type το :year, ζητήθηκαν :requested.', [
                    'name' => $user->name,
                    'remaining' => $remaining,
                    'type' => $leaveType->name,
                    'year' => $year,
                    'requested' => $data['days_count'],
                ]))
                ->danger()
                ->send();

            $this->halt();
        }
    }

    protected function afterCreate(): void
    {
        $admins = User::role('admin')->get();

        foreach ($admins as $admin) {
            $admin->notify(new LeaveRequestSubmitted($this->record));
        }
    }
}
