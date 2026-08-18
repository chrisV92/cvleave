<?php

namespace App\Filament\Widgets;

use App\Models\LeaveRequest;
use Saade\FilamentFullCalendar\Widgets\FullCalendarWidget;

class LeaveCalendar extends FullCalendarWidget
{
    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 1;

    public function fetchEvents(array $info): array
    {
        $isAdmin = auth()->user()?->isAdmin() ?? false;

        $query = LeaveRequest::query()
            ->with(['leaveType', 'user'])
            ->whereHas('user', fn ($query) => $query->where('tenant_id', auth()->user()?->tenant_id))
            ->whereIn('status', [LeaveRequest::STATUS_PENDING, LeaveRequest::STATUS_APPROVED])
            ->where('start_date', '<=', $info['end'])
            ->where('end_date', '>=', $info['start']);

        if (! $isAdmin) {
            $query->where('user_id', auth()->id());
        }

        return $query->get()
            ->filter(fn (LeaveRequest $leaveRequest) => $leaveRequest->user && $leaveRequest->leaveType)
            ->map(function (LeaveRequest $leaveRequest) use ($isAdmin) {
                $title = $isAdmin
                    ? "{$leaveRequest->user->name} — {$leaveRequest->leaveType->name}"
                    : $leaveRequest->leaveType->name;

                if ($leaveRequest->status === LeaveRequest::STATUS_PENDING) {
                    $title .= ' ('.__('εκκρεμεί').')';
                }

                return [
                    'title' => $title,
                    'start' => $leaveRequest->start_date->toDateString(),
                    'end' => $leaveRequest->end_date->addDay()->toDateString(),
                    'color' => $leaveRequest->status === LeaveRequest::STATUS_PENDING
                        ? '#94a3b8'
                        : $leaveRequest->leaveType->color,
                ];
            })->values()->toArray();
    }

    protected function headerActions(): array
    {
        return [];
    }

    protected function modalActions(): array
    {
        return [];
    }

    public function onEventClick(array $event): void
    {
        // Read-only calendar — clicking an event does nothing.
    }

    public function config(): array
    {
        $isGreek = app()->getLocale() === 'el';

        return [
            'headerToolbar' => [
                'left' => 'prev,next today',
                'center' => 'title',
                'right' => 'dayGridMonth,dayGridWeek',
            ],
            'firstDay' => 1,
            'locale' => $isGreek ? 'el' : 'en',
            'buttonText' => $isGreek ? [
                'today' => 'Σήμερα',
                'month' => 'Μήνας',
                'week' => 'Εβδομάδα',
                'day' => 'Ημέρα',
                'list' => 'Λίστα',
            ] : [
                'today' => 'Today',
                'month' => 'Month',
                'week' => 'Week',
                'day' => 'Day',
                'list' => 'List',
            ],
        ];
    }
}
