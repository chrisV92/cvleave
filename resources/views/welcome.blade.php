<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>CvTech — {{ __('Διαχείριση αδειών προσωπικού') }}</title>
    <meta name="description" content="{{ __('Πλατφόρμα διαχείρισης αδειών προσωπικού, με αυτόματο υπολογισμό δικαιώματος κατά την ελληνική νομοθεσία.') }}">
    <link rel="icon" href="{{ asset('img/brand/favicon.svg') }}" type="image/svg+xml">

    <style>
        :root {
            --indigo-50:  #eef2ff;
            --indigo-100: #e0e7ff;
            --indigo-500: #6366f1;
            --indigo-600: #4f46e5;
            --indigo-700: #4338ca;
            --amber-400:  #fbbf24;
            --amber-50:   #fffbeb;
            --ink:        #0f172a;
            --body:       #475569;
            --muted:      #94a3b8;
            --line:       #e2e8f0;
            --surface:    #ffffff;
            --bg:         #f8fafc;
            --radius:     14px;
            --wrap:       1120px;
        }

        * { box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body {
            margin: 0; background: var(--bg); color: var(--body);
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.65; -webkit-font-smoothing: antialiased;
        }
        h1, h2, h3 { color: var(--ink); line-height: 1.2; margin: 0; letter-spacing: -0.02em; }
        p { margin: 0; }
        a { color: inherit; text-decoration: none; }
        .wrap { max-width: var(--wrap); margin: 0 auto; padding: 0 24px; }

        /* ---------- brand ---------- */
        .brand { display: inline-flex; align-items: center; gap: 10px; }
        .brand svg { width: 34px; height: 34px; display: block; }
        .brand-name { font-size: 19px; font-weight: 800; color: var(--ink); letter-spacing: -0.03em; }
        .brand-name span { color: var(--indigo-600); }

        /* ---------- nav ---------- */
        header {
            position: sticky; top: 0; z-index: 20;
            background: rgba(255,255,255,.85); backdrop-filter: blur(10px);
            border-bottom: 1px solid var(--line);
        }
        .nav { display: flex; align-items: center; justify-content: space-between; height: 68px; }
        .nav-links { display: flex; align-items: center; gap: 30px; }
        .nav-links a { font-size: 14.5px; font-weight: 600; color: var(--body); }
        .nav-links a:hover { color: var(--indigo-600); }
        .nav-end { display: flex; align-items: center; gap: 16px; }
        .lang { display: inline-flex; background: #f1f5f9; border-radius: 999px; padding: 3px; }
        .lang-opt {
            padding: 5px 11px; border-radius: 999px; font-size: 12.5px; font-weight: 700;
            letter-spacing: .02em; color: #64748b;
        }
        .lang-opt:hover { color: var(--indigo-600); }
        .lang-opt.is-on { background: #fff; color: var(--indigo-600); box-shadow: 0 1px 2px rgba(15,23,42,.08); }

        .btn {
            display: inline-flex; align-items: center; justify-content: center; gap: 8px;
            font-size: 14.5px; font-weight: 700; padding: 11px 22px; border-radius: 10px;
            border: 1px solid transparent; white-space: nowrap; transition: .15s ease;
        }
        .btn-primary { background: var(--indigo-600); color: #fff; }
        .btn-primary:hover { background: var(--indigo-700); }
        .btn-ghost { background: var(--surface); color: var(--ink); border-color: var(--line); }
        .btn-ghost:hover { border-color: var(--indigo-500); color: var(--indigo-600); }
        .btn-lg { padding: 14px 28px; font-size: 15.5px; }

        /* ---------- hero ---------- */
        .hero { position: relative; overflow: hidden; padding: 82px 0 72px; }
        .hero::before {
            content: ''; position: absolute; inset: 0;
            background:
                radial-gradient(680px 380px at 12% -10%, var(--indigo-100), transparent 62%),
                radial-gradient(520px 320px at 92% 4%, var(--amber-50), transparent 60%);
            pointer-events: none;
        }
                .hero .wrap { position: relative; }
        .hero-grid { display: grid; grid-template-columns: minmax(0, 5fr) minmax(0, 6fr); gap: 54px; align-items: center; }
        .hero p.lead { max-width: 46ch; }

        /* app screenshot, framed like a window so it reads as the product */
        .shot {
            border-radius: 14px; overflow: hidden; background: var(--surface);
            border: 1px solid var(--line);
            box-shadow: 0 30px 60px -26px rgba(15,23,42,.32), 0 6px 16px rgba(15,23,42,.05);
        }
        .shot-bar { display: flex; gap: 6px; padding: 11px 14px; background: #f1f5f9; border-bottom: 1px solid var(--line); }
        .shot-bar span { width: 10px; height: 10px; border-radius: 999px; background: #cbd5e1; }
        .shot img { display: block; width: 100%; height: auto; }
        @media (max-width: 980px) { .hero-grid { grid-template-columns: 1fr; gap: 42px; } }
        .pill {
            display: inline-flex; align-items: center; gap: 8px; margin-bottom: 22px;
            background: var(--surface); border: 1px solid var(--line); color: var(--indigo-700);
            font-size: 13px; font-weight: 700; padding: 6px 14px; border-radius: 999px;
        }
        .pill .dot { width: 7px; height: 7px; border-radius: 999px; background: var(--amber-400); }
        .hero h1 { font-size: clamp(34px, 5.2vw, 52px); max-width: 17ch; }
        .hero h1 em { font-style: normal; color: var(--indigo-600); }
        .hero p.lead { font-size: clamp(16.5px, 2vw, 19px); max-width: 60ch; margin-top: 20px; }
        .hero-cta { display: flex; flex-wrap: wrap; gap: 12px; margin-top: 32px; }
        .hero-note { margin-top: 18px; font-size: 13.5px; color: var(--muted); }

        /* ---------- sections ---------- */
        section { padding: 78px 0; }
        .section-head { max-width: 62ch; margin-bottom: 42px; }
        .eyebrow {
            font-size: 12.5px; font-weight: 800; letter-spacing: .08em; text-transform: uppercase;
            color: var(--indigo-600); margin-bottom: 12px;
        }
        .section-head h2 { font-size: clamp(26px, 3.4vw, 34px); }
        .section-head p { margin-top: 14px; font-size: 16.5px; }

        /* ---------- product cards ---------- */
        .products { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 22px; }
        .product {
            background: var(--surface); border: 1px solid var(--line); border-radius: var(--radius);
            padding: 30px; display: flex; flex-direction: column; gap: 14px;
        }
        .product-top { display: flex; align-items: center; justify-content: space-between; gap: 12px; }
        .product h3 { font-size: 20px; }
        .product ul { margin: 4px 0 0; padding-left: 20px; font-size: 15px; }
        .product ul li { margin-bottom: 7px; }

        /* ---------- features ---------- */
        .features { display: grid; grid-template-columns: repeat(auto-fit, minmax(272px, 1fr)); gap: 20px; }
        .feature-group { margin-bottom: 34px; }
        .feature-group:last-child { margin-bottom: 0; }
        .feature-group > h3 {
            font-size: 13px; font-weight: 700; letter-spacing: .06em; text-transform: uppercase;
            color: var(--indigo-600); margin-bottom: 16px;
        }
        .feature {
            background: var(--surface); border: 1px solid var(--line); border-radius: var(--radius); padding: 26px;
        }
        .feature .ico {
            width: 42px; height: 42px; border-radius: 11px; background: var(--indigo-50);
            display: flex; align-items: center; justify-content: center; margin-bottom: 16px;
        }
        .feature .ico svg { width: 21px; height: 21px; stroke: var(--indigo-600); fill: none; stroke-width: 2; }
        .feature h3 { font-size: 16.5px; margin-bottom: 8px; }
        .feature p { font-size: 14.5px; }

        /* ---------- band ---------- */
        .band { background: var(--ink); color: #cbd5e1; border-radius: 20px; padding: 52px 44px; }
        .band h2 { color: #fff; font-size: clamp(24px, 3vw, 30px); max-width: 22ch; }
        .band p { margin-top: 14px; max-width: 58ch; font-size: 16px; }
        .band .hero-cta { margin-top: 28px; }
        .band .btn-ghost { background: transparent; color: #fff; border-color: #334155; }
        .band .btn-ghost:hover { border-color: var(--indigo-500); color: #fff; }

        /* ---------- footer ---------- */
        footer { border-top: 1px solid var(--line); padding: 34px 0; margin-top: 78px; }
        .foot { display: flex; flex-wrap: wrap; gap: 16px; align-items: center; justify-content: space-between; }
        .foot p { font-size: 13.5px; color: var(--muted); }

        @media (max-width: 720px) {
            .nav-links { display: none; }
            .nav-end { gap: 10px; }
            .band { padding: 38px 26px; border-radius: 16px; }
            section { padding: 58px 0; }
        }
    </style>
</head>
<body>

@php
    $mark = '<svg viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
        <defs><linearGradient id="bg1" x1="0" y1="0" x2="1" y2="1">
        <stop offset="0" stop-color="#6366f1"/><stop offset="1" stop-color="#4338ca"/></linearGradient></defs>
        <rect width="64" height="64" rx="15" fill="url(#bg1)"/>
        <path d="M18 23 L32 45 L46 23" fill="none" stroke="#fff" stroke-width="7" stroke-linecap="round" stroke-linejoin="round"/>
        <circle cx="50" cy="16" r="4.5" fill="#fbbf24"/></svg>';
@endphp

@php
    // The product shots are captures of a real interface, so each language
    // needs its own. Same framing and same dimensions in both, so switching
    // language does not shift the layout.
    $shot = fn (string $name) => asset('img/landing/'.$name.(app()->getLocale() === 'en' ? '-en' : '').'.png');
@endphp

<header>
    <div class="wrap nav">
        <a href="#" class="brand">
            {!! $mark !!}
            <span class="brand-name">Cv<span>Tech</span></span>
        </a>
        <nav class="nav-links">
            <a href="#products">{{ __('Υπηρεσίες') }}</a>
            <a href="#features">{{ __('Δυνατότητες') }}</a>
            <a href="#board">{{ __('Εργασίες') }}</a>
            <a href="#law">{{ __('Ελληνική νομοθεσία') }}</a>
        </nav>
        <div class="nav-end">
            {{-- The route stores the choice in the session and sends you back,
                 so the switch works from here as well as inside the panel. --}}
            <div class="lang" role="group" aria-label="{{ __('Γλώσσα') }}">
                @foreach (['el' => 'ΕΛ', 'en' => 'EN'] as $locale => $short)
                    <a
                        href="{{ route('locale.switch', $locale) }}"
                        class="lang-opt @if (app()->getLocale() === $locale) is-on @endif"
                        @if (app()->getLocale() === $locale) aria-current="true" @endif
                    >{{ $short }}</a>
                @endforeach
            </div>

            <a href="{{ url('/admin') }}" class="btn btn-primary">{{ __('Σύνδεση') }}</a>
        </div>
    </div>
</header>

<div class="hero">
    <div class="wrap hero-grid">
        <div class="hero-copy">
            <span class="pill"><span class="dot"></span>{{ __('Φτιαγμένο για ελληνικές επιχειρήσεις') }}</span>
            <h1>{{ __('Οι άδειες της ομάδας σου,') }} <em>{{ __('χωρίς υπολογισμούς στο χέρι.') }}</em></h1>
            <p class="lead">
                {{ __('Το CvTech υπολογίζει αυτόματα το δικαίωμα άδειας κάθε υπαλλήλου βάσει του Α.Ν. 539/1945, κρατάει τα υπόλοιπα ενημερωμένα και δίνει στη διοίκηση εικόνα σε πραγματικό χρόνο.') }}
            </p>
            <div class="hero-cta">
                <a href="{{ url('/admin') }}" class="btn btn-primary btn-lg">{{ __('Σύνδεση στην πλατφόρμα') }}</a>
                <a href="#features" class="btn btn-ghost btn-lg">{{ __('Δες τι κάνει') }}</a>
            </div>
            <p class="hero-note">{{ __('Κάθε εταιρεία με τον δικό της χώρο, τους δικούς της τύπους αδειών και τους δικούς της κανόνες.') }}</p>
        </div>

        <div class="hero-visual">
            <figure class="shot" style="margin:0;">
                <div class="shot-bar"><span></span><span></span><span></span></div>
                <img src="{{ $shot('app-dashboard') }}"
                     alt="{{ __('Ο πίνακας ελέγχου του CvTech: υπόλοιπα αδειών ανά τύπο και ημερολόγιο ομάδας.') }}"
                     loading="lazy" width="1600" height="786">
            </figure>
        </div>
    </div>
</div>

<section id="products">
    <div class="wrap">
        <div class="section-head">
            <p class="eyebrow">{{ __('Υπηρεσίες') }}</p>
            <h2>{{ __('Μία πλατφόρμα, δύο εργαλεία') }}</h2>
            <p>{{ __('Ξεκινήσαμε από αυτό που πονάει περισσότερο στις ελληνικές ΜμΕ — τον υπολογισμό των αδειών — και συνεχίσαμε με τη δουλειά που τρέχει δίπλα τους. Ίδιοι χρήστες, ίδιος χώρος, καμία διπλή καταχώριση.') }}</p>
        </div>

        <div class="products">
            <article class="product">
                <div class="product-top">
                    <h3>{{ __('Διαχείριση Αδειών') }}</h3>
                </div>
                <p>{{ __('Από την υποβολή της αίτησης μέχρι την έγκριση και την αναφορά — με τα υπόλοιπα να ενημερώνονται μόνα τους.') }}</p>
                <ul>
                    <li>{{ __('Αυτόματο δικαίωμα κατά Α.Ν. 539/1945') }}</li>
                    <li>{{ __('Ολόκληρη μέρα, μισή μέρα ή ώρες') }}</li>
                    <li>{{ __('Έγκριση με έλεγχο υπολοίπου και επικαλύψεων') }}</li>
                    <li>{{ __('Ημερολόγιο ομάδας και αναφορές PDF/Excel') }}</li>
                </ul>
            </article>

            <article class="product">
                <div class="product-top">
                    <h3>{{ __('Διαχείριση Εργασιών') }}</h3>
                </div>
                <p>{{ __('Έργα, αναθέσεις και προθεσμίες, στον ίδιο χώρο με το προσωπικό σου — ώστε να ξέρεις ποιος είναι διαθέσιμος και πότε.') }}</p>
                <ul>
                    <li>{{ __('Πίνακας kanban με μεταφορά καρτών') }}</li>
                    <li>{{ __('Στήλες και πεδία που ορίζει η κάθε εταιρεία') }}</li>
                    <li>{{ __('Χρονομέτρηση, συνημμένα αρχεία και σχόλια') }}</li>
                    <li>{{ __('Ίδιοι χρήστες με τις άδειες, για ρεαλιστικό προγραμματισμό') }}</li>
                </ul>
            </article>
        </div>
    </div>
</section>

<section id="features" style="padding-top: 0;">
    <div class="wrap">
        <div class="section-head">
            <p class="eyebrow">{{ __('Δυνατότητες') }}</p>
            <h2>{{ __('Ό,τι χρειάζεται μια μικρή εταιρεία, σε δύο ενότητες') }}</h2>
        </div>

        @php
            $leaveFeatures = [
                ['scale',    __('Ελληνική νομοθεσία'),      __('Κλιμακωτά 20/21/22/25/26 μέρες βάσει έτους απασχόλησης και συνολικής προϋπηρεσίας, με αναλογία στο πρώτο έτος.')],
                    ['clock',    __('Μερική άδεια'),            __('Ολόκληρη μέρα, μισή μέρα ή ώρες — με αυτόματη μετατροπή σε ισοδύναμο ημερών.')],
                    ['refresh',  __('Μεταφορά υπολοίπου'),      __('Οι αχρησιμοποίητες μέρες περνούν στο νέο έτος, με προθεσμία που ορίζεις εσύ.')],
                    ['check',    __('Έλεγχοι πριν την έγκριση'),__('Επικαλύψεις και ανεπαρκές υπόλοιπο μπλοκάρονται τόσο στην υποβολή όσο και στην έγκριση.')],
                    ['calendar', __('Ημερολόγιο ομάδας'),       __('Ο υπάλληλος βλέπει τις δικές του άδειες, ο διαχειριστής ολόκληρη την εταιρεία.')],
                    ['bell',     __('Ειδοποιήσεις'),            __('Email και in-app σε κάθε υποβολή, έγκριση ή απόρριψη, με υπενθυμίσεις πριν την έναρξη.')],
                    ['doc',      __('Αναφορές & εξαγωγές'),     __('Αναφορές PDF ανά υπάλληλο ή συνολικές, και εξαγωγή σε Excel.')],
                    ['mail',     __('Προσκλήσεις με email'),    __('Ο υπάλληλος ορίζει μόνος του κωδικό — δεν μοιράζεις κωδικούς με το χέρι.')],
                ['globe',    __('Ελληνικά & Αγγλικά'),      __('Εναλλαγή γλώσσας εν κινήσει, για ομάδες με ξενόγλωσσο προσωπικό.')],
            ];

            $taskFeatures = [
                ['columns',  __('Πίνακας kanban'),          __('Οι εργασίες σε στήλες, με μεταφορά καρτών. Η αλλαγή αποθηκεύεται με το που την αφήσεις.')],
                ['layers',   __('Στήλες ανά έργο'),         __('Κάθε έργο ορίζει τη δική του ροή — οι πωλήσεις δεν κινούνται όπως η ανάπτυξη.')],
                ['tag',      __('Δικά σου πεδία'),          __('Ποσά, ημερομηνίες, λίστες, χρήστες. Ανά εταιρεία ή ανά έργο, χωρίς προγραμματιστή.')],
                ['clock',    __('Χρονομέτρηση'),            __('Έναρξη και διακοπή σε κάθε εργασία, με ένα μόνο χρονόμετρο ανά άτομο.')],
                ['paperclip',__('Αρχεία και σχόλια'),       __('Συνημμένα με προεπισκόπηση εικόνων και συζήτηση πάνω στην εργασία.')],
                ['users',    __('Ίδιοι χρήστες'),           __('Οι άνθρωποι που καταθέτουν άδεια είναι οι ίδιοι που αναλαμβάνουν τις εργασίες.')],
            ];

            $icons = [
                    'scale'    => '<path d="M12 3v18M5 7h14M7 7l-3 7h6zM17 7l-3 7h6z"/>',
                    'clock'    => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
                    'refresh'  => '<path d="M21 12a9 9 0 1 1-3-6.7"/><path d="M21 4v5h-5"/>',
                    'check'    => '<path d="M20 6 9 17l-5-5"/>',
                    'calendar' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M8 3v4M16 3v4M3 11h18"/>',
                    'bell'     => '<path d="M18 8a6 6 0 1 0-12 0c0 7-3 8-3 8h18s-3-1-3-8"/><path d="M13.7 21a2 2 0 0 1-3.4 0"/>',
                    'doc'      => '<path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/><path d="M14 3v5h5"/>',
                    'mail'     => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>',
                'globe'    => '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a15 15 0 0 1 0 18a15 15 0 0 1 0-18"/>',
                'columns'  => '<rect x="3" y="4" width="5" height="16" rx="1"/><rect x="9.5" y="4" width="5" height="11" rx="1"/><rect x="16" y="4" width="5" height="14" rx="1"/>',
                'layers'   => '<path d="m12 3 9 5-9 5-9-5z"/><path d="m3 13 9 5 9-5"/>',
                'tag'      => '<path d="M20 12 12 20l-8-8V4h8z"/><circle cx="8" cy="8" r="1.4"/>',
                'paperclip'=> '<path d="M21 12.5 12.5 21a5 5 0 0 1-7-7l8-8a3.5 3.5 0 0 1 5 5l-8 8a2 2 0 0 1-3-3l7.5-7.5"/>',
                'users'    => '<circle cx="9" cy="8" r="3.2"/><path d="M2.5 20a6.5 6.5 0 0 1 13 0"/><path d="M16 5.5a3.2 3.2 0 0 1 0 6M17.5 20a6.5 6.5 0 0 0-2.2-4.9"/>',
            ];
        @endphp

        <div class="feature-group">
            <h3>{{ __('Άδειες') }}</h3>
            <div class="features">
                @foreach ($leaveFeatures as [$icon, $title, $text])
                    <div class="feature">
                        <div class="ico">
                            <svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">{!! $icons[$icon] !!}</svg>
                        </div>
                        <h3>{{ $title }}</h3>
                        <p>{{ $text }}</p>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="feature-group">
            <h3>{{ __('Έργα & Εργασίες') }}</h3>
            <div class="features">
                @foreach ($taskFeatures as [$icon, $title, $text])
                    <div class="feature">
                        <div class="ico">
                            <svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">{!! $icons[$icon] !!}</svg>
                        </div>
                        <h3>{{ $title }}</h3>
                        <p>{{ $text }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</section>

<section id="board" style="padding-top: 0;">
    <div class="wrap">
        <div class="section-head">
            <p class="eyebrow">{{ __('Διαχείριση Εργασιών') }}</p>
            <h2>{{ __('Η δουλειά σε στήλες, όχι σε λίστες') }}</h2>
            <p>{{ __('Κάθε έργο έχει τον δικό του πίνακα και τις δικές του στήλες. Σύρεις μια κάρτα, η κατάσταση αλλάζει — χωρίς φόρμες και χωρίς αποθήκευση.') }}</p>
        </div>

        <figure class="shot" style="margin: 0;">
            <div class="shot-bar"><span></span><span></span><span></span></div>
            <img src="{{ $shot('kanban-board') }}"
                 alt="{{ __('Πίνακας έργου στο CvTech: εργασίες σε στήλες, με προτεραιότητα, προθεσμία και ανάθεση σε κάθε κάρτα.') }}"
                 loading="lazy" width="1278" height="426">
        </figure>
    </div>
</section>

<section id="law" style="padding-top: 0;">
    <div class="wrap">
        <div class="band">
            <h2>{{ __('Ο νόμος είναι περίπλοκος. Η εφαρμογή δεν χρειάζεται να είναι.') }}</h2>
            <p>
                {{ __('Το δικαίωμα άδειας δεν εξαρτάται μόνο από τα χρόνια στη δική σου εταιρεία, αλλά και από τη συνολική προϋπηρεσία του υπαλλήλου σε κάθε εργοδότη. Τα διεθνή εργαλεία σπάνια το υπολογίζουν σωστά — εδώ είναι ο πυρήνας.') }}
            </p>
            <div class="hero-cta">
                <a href="{{ url('/admin') }}" class="btn btn-primary btn-lg">{{ __('Ξεκίνα τώρα') }}</a>
                <a href="#products" class="btn btn-ghost btn-lg">{{ __('Δες τις υπηρεσίες') }}</a>
            </div>
        </div>
    </div>
</section>

<footer>
    <div class="wrap foot">
        <a href="#" class="brand">
            {!! $mark !!}
            <span class="brand-name">Cv<span>Tech</span></span>
        </a>
        <p>© {{ date('Y') }} CvTech — {{ __('Σύστημα διαχείρισης αδειών προσωπικού') }}</p>
    </div>
</footer>

</body>
</html>
