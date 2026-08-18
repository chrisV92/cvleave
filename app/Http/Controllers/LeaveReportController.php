<?php

namespace App\Http\Controllers;

use App\Models\LeaveRequest;
use App\Models\User;
use App\Services\LeaveBalanceService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class LeaveReportController extends Controller
{
    public function employee(User $user, LeaveBalanceService $service): Response
    {
        $viewer = auth()->user();
        abort_unless($viewer && ($viewer->isAdmin() || $viewer->id === $user->id), 403);

        $year = now()->year;
        $summary = $service->summaryForUser($user, $year);

        $history = $user->leaveRequests()
            ->with('leaveType')
            ->orderByDesc('start_date')
            ->get();

        $pdf = Pdf::loadView('reports.employee-leave-report', [
            'user' => $user,
            'year' => $year,
            'summary' => $summary,
            'history' => $history,
            'generatedAt' => now(),
        ])->setPaper('a4');

        $filename = 'leave-report-'.str($user->name)->slug().'-'.$year.'.pdf';

        return $pdf->download($filename);
    }

    public function allEmployees(): Response
    {
        $viewer = auth()->user();
        abort_unless($viewer && $viewer->isAdmin(), 403);

        $leaveRequests = LeaveRequest::query()
            ->with(['user', 'leaveType', 'reviewer'])
            ->orderBy('user_id')
            ->orderByDesc('start_date')
            ->get();

        $pdf = Pdf::loadView('reports.all-employees-report', [
            'leaveRequests' => $leaveRequests,
            'generatedAt' => now(),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('leave-report-all-employees-'.now()->format('Y-m-d').'.pdf');
    }
}
