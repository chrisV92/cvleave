<?php

namespace App\Filament\Resources\LeaveRequests\Pages;

use App\Filament\Resources\LeaveRequests\LeaveRequestResource;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Services\LeaveBalanceService;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Carbon;

class EditLeaveRequest extends EditRecord
{
    protected static string $resource = LeaveRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (! (auth()->user()?->isAdmin() ?? false)) {
            $data['user_id'] = $this->record->user_id;
            $data['status'] = LeaveRequest::STATUS_PENDING;
        }

        $data['end_date'] ??= $data['start_date'];

        return $data;
    }

    protected function beforeSave(): void
    {
        if (auth()->user()?->isAdmin() ?? false) {
            return;
        }

        $data = $this->form->getState();

        $leaveType = LeaveType::find($data['leave_type_id']);
        $year = Carbon::parse($data['start_date'])->year;

        $service = app(LeaveBalanceService::class);

        if ($service->hasOverlap($this->record->user, $data['start_date'], $data['end_date'], excludeLeaveRequestId: $this->record->id)) {
            Notification::make()
                ->title(__('Επικάλυψη με υπάρχουσα άδεια'))
                ->body(__('Έχεις ήδη άδεια (εκκρεμή ή εγκεκριμένη) που καλύπτει αυτό το διάστημα. Πρέπει να περάσει τουλάχιστον μία μέρα από τη λήξη προηγούμενης άδειας.'))
                ->danger()
                ->send();

            $this->halt();
        }

        // Employees can only edit their own PENDING requests (see canEdit()), which are
        // never counted in usedDays() — so no adjustment is needed here.
        $remaining = $service->remainingDays($this->record->user, $leaveType, $year);

        if ($data['days_count'] > $remaining) {
            Notification::make()
                ->title(__('Ανεπαρκές υπόλοιπο ημερών'))
                ->body(__('Έχεις μόνο :remaining διαθέσιμες μέρες για :type το :year.', [
                    'remaining' => $remaining,
                    'type' => $leaveType->name,
                    'year' => $year,
                ]))
                ->danger()
                ->send();

            $this->halt();
        }
    }
}
