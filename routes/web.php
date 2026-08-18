<?php

use App\Http\Controllers\InvitationController;
use App\Http\Controllers\LeaveReportController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/locale/{locale}', function (string $locale) {
    if (in_array($locale, ['el', 'en'], true)) {
        session(['locale' => $locale]);
    }

    return back();
})->name('locale.switch');

// Public on purpose: the recipient has no account password yet. The token in
// the URL is the credential.
Route::get('/invite/{token}', [InvitationController::class, 'show'])->name('invitation.show');
Route::post('/invite/{token}', [InvitationController::class, 'accept'])->name('invitation.accept');

Route::get('/reports/employee/{user}', [LeaveReportController::class, 'employee'])
    ->middleware('auth')
    ->name('reports.employee-leave');

Route::get('/reports/all-employees', [LeaveReportController::class, 'allEmployees'])
    ->middleware('auth')
    ->name('reports.all-employees-leave');
