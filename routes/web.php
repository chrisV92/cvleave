<?php

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

Route::get('/reports/employee/{user}', [LeaveReportController::class, 'employee'])
    ->middleware('auth')
    ->name('reports.employee-leave');

Route::get('/reports/all-employees', [LeaveReportController::class, 'allEmployees'])
    ->middleware('auth')
    ->name('reports.all-employees-leave');
