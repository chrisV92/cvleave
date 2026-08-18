<x-filament-panels::page>
<style>
    html { scroll-behavior: smooth; }
    .kb-shot { max-width: 100%; border-radius: 8px; border: 1px solid #e4e4e7; box-shadow: 0 1px 3px rgba(0,0,0,0.08); margin: 12px 0 4px 0; }
    .kb-section p { line-height: 1.65; margin: 0 0 12px 0; }
    .kb-section ol, .kb-section ul { line-height: 1.65; margin: 0 0 12px 0; padding-left: 22px; }
    .kb-section ol li, .kb-section ul li { margin-bottom: 6px; }
    .kb-layout { display: grid; grid-template-columns: 240px 1fr; gap: 24px; align-items: start; }
    .kb-toc { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: 2px; }
    .kb-toc a { display: block; padding: 8px 12px; border-radius: 8px; text-decoration: none; color: #71717a; font-weight: 600; font-size: 14px; border-left: 3px solid transparent; }
    .kb-toc a:hover { background-color: #fafafa; color: #4f46e5; }
    .kb-toc a.kb-toc-active { background-color: #eef2ff; color: #4f46e5; border-left-color: #6366f1; }
    [id] { scroll-margin-top: 100px; }
    @media (max-width: 900px) {
        .kb-layout { grid-template-columns: 1fr; }
        .kb-nav-sticky { position: static !important; }
    }
</style>

<div
    x-data="{
        active: 'overview',
        ids: ['overview', 'tenants', 'users', 'access'],
        init() {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) { this.active = entry.target.id; }
                });
            }, { rootMargin: '-96px 0px -50% 0px', threshold: 0 });
            this.ids.forEach((id) => {
                const el = document.getElementById(id);
                if (el) observer.observe(el);
            });
        },
    }"
    class="kb-layout"
>
    <nav class="kb-nav-sticky" style="position: sticky; top: 90px;">
        <x-filament::section heading="{{ __('Γρήγορη Πλοήγηση') }}" icon="heroicon-o-list-bullet">
            <ul class="kb-toc">
                <li><a href="#overview" :class="active === 'overview' && 'kb-toc-active'">1. {{ __('Τι είναι το Platform Panel') }}</a></li>
                <li><a href="#tenants" :class="active === 'tenants' && 'kb-toc-active'">2. {{ __('Διαχείριση Εταιρειών (Tenants)') }}</a></li>
                <li><a href="#users" :class="active === 'users' && 'kb-toc-active'">3. {{ __('Χρήστες όλων των εταιρειών') }}</a></li>
                <li><a href="#access" :class="active === 'access' && 'kb-toc-active'">4. {{ __('Ποιος έχει πρόσβαση εδώ') }}</a></li>
            </ul>
        </x-filament::section>
    </nav>

    <div style="display: flex; flex-direction: column; gap: 24px;">

    <x-filament::section id="overview" heading="{{ __('Τι είναι το Platform Panel') }}" icon="heroicon-o-globe-alt">
        <div class="kb-section">
            <p>{{ __('Αυτό το panel (/platform) είναι ξεχωριστό από το κανονικό admin panel κάθε εταιρείας (/admin/{εταιρεία}). Εδώ βλέπεις και διαχειρίζεσαι όλες τις εταιρείες (tenants) της εφαρμογής μαζί — όχι μόνο μία.') }}</p>
            <p>{{ __('Το admin panel μιας εταιρείας δείχνει μόνο τους δικούς της υπαλλήλους και τύπους άδειας. Το Platform panel είναι το επίπεδο πάνω από αυτό, για εσένα ως ιδιοκτήτη/διαχειριστή της πλατφόρμας.') }}</p>
        </div>
    </x-filament::section>

    <x-filament::section id="tenants" heading="{{ __('Διαχείριση Εταιρειών (Tenants)') }}" icon="heroicon-o-building-office-2">
        <div class="kb-section">
            <p>{{ __('Στο μενού "Εταιρείες" βλέπεις όλες τις εταιρείες-πελάτες, με αριθμό χρηστών και τύπων άδειας η καθεμία.') }}</p>
            <img src="{{ asset('img/kb/16-platform-tenants.png') }}" class="kb-shot" alt="Platform tenants list">
            <p>{{ __('Πατώντας "Προσθήκη Εταιρείας" δίνεις μόνο Όνομα και Slug (χρησιμοποιείται στο URL της, π.χ. /admin/slug). Μόλις δημιουργηθεί η εταιρεία, το σύστημα προσθέτει αυτόματα:') }}</p>
            <ul>
                <li>{{ __('τους 3 βασικούς τύπους άδειας (Κανονική, Αναρρωτική, Άνευ Αποδοχών)') }}</li>
                <li>{{ __('τους ρόλους Admin και Υπάλληλος, έτοιμους για ανάθεση σε χρήστες αυτής της εταιρείας') }}</li>
            </ul>
            <img src="{{ asset('img/kb/17-platform-tenant-create.png') }}" class="kb-shot" alt="Create tenant form">
            <p>{{ __('Δεν χρειάζεται να τα φτιάξεις εσύ χειροκίνητα — ο tenant admin της εταιρείας μπορεί μετά να τα προσαρμόσει ή να προσθέσει δικούς του τύπους από το δικό του panel.') }}</p>
        </div>
    </x-filament::section>

    <x-filament::section id="users" heading="{{ __('Χρήστες όλων των εταιρειών') }}" icon="heroicon-o-users">
        <div class="kb-section">
            <p>{{ __('Στο μενού "Χρήστες" βλέπεις τους χρήστες όλων των εταιρειών μαζί, με στήλη "Εταιρεία" και φίλτρο για να περιορίσεις τη λίστα σε μία συγκεκριμένη.') }}</p>
            <img src="{{ asset('img/kb/18-platform-users.png') }}" class="kb-shot" alt="Platform users list across tenants">
            <p>{{ __('Όταν δημιουργείς νέο χρήστη εδώ, επιλέγεις υποχρεωτικά σε ποια Εταιρεία θα ανήκει, και τον Ρόλο του (Admin ή Υπάλληλος) μέσα σε αυτήν την εταιρεία.') }}</p>
            <img src="{{ asset('img/kb/19-platform-user-create.png') }}" class="kb-shot" alt="Create user form with tenant and role selects">
            <p><strong>{{ __('Σημείωση:') }}</strong> {{ __('ο ρόλος ενός χρήστη είναι πάντα σχετικός με τη συγκεκριμένη εταιρεία του — δεν υπάρχει έννοια "global admin" εκτός από το platform flag παρακάτω.') }}</p>
        </div>
    </x-filament::section>

    <x-filament::section id="access" heading="{{ __('Ποιος έχει πρόσβαση εδώ') }}" icon="heroicon-o-shield-check">
        <div class="kb-section">
            <p>{{ __('Η πρόσβαση στο /platform ελέγχεται από ένα ξεχωριστό flag στον χρήστη ("Platform Admin"), ανεξάρτητο από τον ρόλο του μέσα σε κάποια εταιρεία. Ένας tenant admin ΔΕΝ βλέπει αυτόματα το Platform panel, και το αντίστροφο — ένας platform admin δεν έχει αυτόματα ρόλο admin μέσα σε κάποια συγκεκριμένη εταιρεία.') }}</p>
            <p>{{ __('Αυτό το flag ενεργοποιείται μόνο χειροκίνητα (τεχνικά, όχι από το UI ακόμα) — κράτησέ το σε πολύ λίγους λογαριασμούς εμπιστοσύνης.') }}</p>
        </div>
    </x-filament::section>

    </div>
</div>
</x-filament-panels::page>
