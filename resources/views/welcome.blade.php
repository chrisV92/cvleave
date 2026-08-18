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
        .hero { position: relative; overflow: hidden; padding: 88px 0 60px; }
        .hero::before {
            content: ''; position: absolute; inset: 0;
            background:
                radial-gradient(680px 380px at 12% -10%, var(--indigo-100), transparent 62%),
                radial-gradient(520px 320px at 92% 4%, var(--amber-50), transparent 60%);
            pointer-events: none;
        }
        .hero .wrap { position: relative; }
        .pill {
            display: inline-flex; align-items: center; gap: 8px; margin-bottom: 22px;
            background: var(--surface); border: 1px solid var(--line); color: var(--indigo-700);
            font-size: 13px; font-weight: 700; padding: 6px 14px; border-radius: 999px;
        }
        .pill .dot { width: 7px; height: 7px; border-radius: 999px; background: var(--amber-400); }
        .hero h1 { font-size: clamp(34px, 5.2vw, 54px); max-width: 860px; }
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
        .product.is-live { border-color: var(--indigo-500); box-shadow: 0 0 0 3px var(--indigo-50); }
        .product-top { display: flex; align-items: center; justify-content: space-between; gap: 12px; }
        .product h3 { font-size: 20px; }
        .tag {
            font-size: 11.5px; font-weight: 800; letter-spacing: .04em; text-transform: uppercase;
            padding: 5px 11px; border-radius: 999px; white-space: nowrap;
        }
        .tag-live { background: #ecfdf5; color: #047857; }
        .tag-soon { background: #f1f5f9; color: #64748b; }
        .product ul { margin: 4px 0 0; padding-left: 20px; font-size: 15px; }
        .product ul li { margin-bottom: 7px; }

        /* ---------- features ---------- */
        .features { display: grid; grid-template-columns: repeat(auto-fit, minmax(272px, 1fr)); gap: 20px; }
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

<header>
    <div class="wrap nav">
        <a href="#" class="brand">
            {!! $mark !!}
            <span class="brand-name">Cv<span>Tech</span></span>
        </a>
        <nav class="nav-links">
            <a href="#products">{{ __('Υπηρεσίες') }}</a>
            <a href="#features">{{ __('Δυνατότητες') }}</a>
            <a href="#law">{{ __('Ελληνική νομοθεσία') }}</a>
        </nav>
        <a href="{{ url('/admin') }}" class="btn btn-primary">{{ __('Σύνδεση') }}</a>
    </div>
</header>

<div class="hero">
    <div class="wrap">
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
</div>

<section id="products">
    <div class="wrap">
        <div class="section-head">
            <p class="eyebrow">{{ __('Υπηρεσίες') }}</p>
            <h2>{{ __('Μία πλατφόρμα, ένα εργαλείο τη φορά') }}</h2>
            <p>{{ __('Ξεκινήσαμε από αυτό που πονάει περισσότερο στις ελληνικές ΜμΕ: τον υπολογισμό και την παρακολούθηση των αδειών.') }}</p>
        </div>

        <div class="products">
            <article class="product is-live">
                <div class="product-top">
                    <h3>{{ __('Διαχείριση Αδειών') }}</h3>
                    <span class="tag tag-live">{{ __('Διαθέσιμο') }}</span>
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
                    <span class="tag tag-soon">{{ __('Σύντομα') }}</span>
                </div>
                <p>{{ __('Έργα, αναθέσεις και προθεσμίες, στον ίδιο χώρο με το προσωπικό σου — ώστε να ξέρεις ποιος είναι διαθέσιμος και πότε.') }}</p>
                <ul>
                    <li>{{ __('Έργα και αναθέσεις ανά ομάδα') }}</li>
                    <li>{{ __('Σύνδεση με τις άδειες για ρεαλιστικό προγραμματισμό') }}</li>
                </ul>
            </article>
        </div>
    </div>
</section>

<section id="features" style="padding-top: 0;">
    <div class="wrap">
        <div class="section-head">
            <p class="eyebrow">{{ __('Δυνατότητες') }}</p>
            <h2>{{ __('Ό,τι χρειάζεται μια εταιρεία για τις άδειες') }}</h2>
        </div>

        <div class="features">
            @php
                $features = [
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
                ];
            @endphp

            @foreach ($features as [$icon, $title, $text])
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
