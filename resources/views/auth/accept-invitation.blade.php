<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Πρόσκληση στο CVLeave') }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center;
            background-color: #f4f4f5; padding: 24px;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            color: #18181b;
        }
        .card { width: 100%; max-width: 420px; background: #fff; border-radius: 12px; padding: 32px; box-shadow: 0 1px 3px rgba(0,0,0,.08); }
        .brand { font-size: 22px; font-weight: 800; letter-spacing: -.02em; margin: 0 0 4px; }
        .tagline { font-size: 12px; font-weight: 500; letter-spacing: .06em; text-transform: uppercase; color: #a1a1aa; margin: 0 0 24px; }
        h1 { font-size: 19px; font-weight: 700; margin: 0 0 8px; }
        p { font-size: 14px; line-height: 1.6; color: #52525b; margin: 0 0 20px; }
        label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; }
        input[type=password] { width: 100%; padding: 10px 12px; font-size: 14px; border: 1px solid #d4d4d8; border-radius: 8px; margin-bottom: 16px; }
        input[type=password]:focus { outline: 2px solid #d97706; outline-offset: -1px; border-color: #d97706; }
        button { width: 100%; padding: 12px; font-size: 14px; font-weight: 700; color: #fff; background: #d97706; border: 0; border-radius: 8px; cursor: pointer; }
        button:hover { background: #b45309; }
        .errors { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; font-size: 13px; border-radius: 8px; padding: 10px 12px; margin-bottom: 16px; }
        .errors ul { margin: 0; padding-left: 18px; }
        .muted { font-size: 13px; color: #71717a; }
        .link { color: #b45309; font-weight: 600; }
    </style>
</head>
<body>
<div class="card">
    <p class="brand">CVLeave</p>
    <p class="tagline">{{ __('Σύστημα διαχείρισης αδειών προσωπικού') }}</p>

    @if ($invalid)
        <h1>{{ __('Ο σύνδεσμος δεν ισχύει') }}</h1>
        <p>{{ __('Η πρόσκληση μπορεί να έχει λήξει ή να έχει ήδη χρησιμοποιηθεί. Ζήτησε από τον διαχειριστή της εταιρείας σου να σου στείλει νέα.') }}</p>
        <p class="muted"><a class="link" href="{{ url('/admin/login') }}">{{ __('Σύνδεση') }}</a></p>
    @else
        <h1>{{ __('Καλώς ήρθες, :name', ['name' => $user->name]) }}</h1>
        <p>{{ __('Όρισε έναν κωδικό για τον λογαριασμό σου (:email) και είσαι έτοιμος.', ['email' => $user->email]) }}</p>

        @if ($errors->any())
            <div class="errors">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('invitation.accept', ['token' => $token]) }}">
            @csrf

            <label for="password">{{ __('Κωδικός') }}</label>
            <input id="password" type="password" name="password" required autofocus autocomplete="new-password">

            <label for="password_confirmation">{{ __('Επιβεβαίωση κωδικού') }}</label>
            <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password">

            <button type="submit">{{ __('Αποθήκευση και σύνδεση') }}</button>
        </form>
    @endif
</div>
</body>
</html>
