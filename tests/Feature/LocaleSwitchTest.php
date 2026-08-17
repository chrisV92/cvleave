<?php

it('switches the session locale to English', function () {
    $this->get('/locale/en');

    expect(session('locale'))->toBe('en');
});

it('switches the session locale to Greek', function () {
    session(['locale' => 'en']);

    $this->get('/locale/el');

    expect(session('locale'))->toBe('el');
});

it('ignores an invalid locale value', function () {
    session(['locale' => 'el']);

    $this->get('/locale/fr');

    expect(session('locale'))->toBe('el');
});

it('applies the session locale to the app on the next request', function () {
    $this->withSession(['locale' => 'en'])->get('/');

    expect(app()->getLocale())->toBe('en');
});
