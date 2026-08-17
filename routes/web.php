<?php

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
