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
    .kb-toc a:hover { background-color: #fafafa; color: #b45309; }
    .kb-toc a.kb-toc-active { background-color: #fffbeb; color: #b45309; border-left-color: #d97706; }
    [id] { scroll-margin-top: 100px; }
    @media (max-width: 900px) {
        .kb-layout { grid-template-columns: 1fr; }
        .kb-nav-sticky { position: static !important; }
    }
</style>

<div
    x-data="{
        active: 'dashboard',
        ids: ['dashboard', 'approve-reject', 'manage-users', 'leave-types', 'export-report', 'balance-override', 'reminders'],
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
                <li><a href="#dashboard" :class="active === 'dashboard' && 'kb-toc-active'">1. {{ __('Ο Πίνακας Ελέγχου του Admin') }}</a></li>
                <li><a href="#approve-reject" :class="active === 'approve-reject' && 'kb-toc-active'">2. {{ __('Έγκριση / Απόρριψη Αιτήσεων') }}</a></li>
                <li><a href="#manage-users" :class="active === 'manage-users' && 'kb-toc-active'">3. {{ __('Διαχείριση Χρηστών') }}</a></li>
                <li><a href="#leave-types" :class="active === 'leave-types' && 'kb-toc-active'">4. {{ __('Τύποι Αδειών & Υπολογισμός Δικαιώματος') }}</a></li>
                <li><a href="#export-report" :class="active === 'export-report' && 'kb-toc-active'">5. {{ __('Εξαγωγή & Αναφορές PDF') }}</a></li>
                <li><a href="#balance-override" :class="active === 'balance-override' && 'kb-toc-active'">6. {{ __('Χειροκίνητη Ρύθμιση Υπολοίπου (Override)') }}</a></li>
                <li><a href="#reminders" :class="active === 'reminders' && 'kb-toc-active'">7. {{ __('Αυτόματες Υπενθυμίσεις') }}</a></li>
            </ul>
        </x-filament::section>
    </nav>

    <div style="display: flex; flex-direction: column; gap: 24px;">

    <x-filament::section id="dashboard" heading="{{ __('Ο Πίνακας Ελέγχου του Admin') }}" icon="heroicon-o-home">
        <div class="kb-section">
            <p>{{ __('Ως admin βλέπεις το δικό σου προσωπικό υπόλοιπο ημερών στις κάρτες, αλλά το ημερολόγιο από κάτω δείχνει τις άδειες όλων των υπαλλήλων — όχι μόνο τις δικές σου.') }}</p>
            <p><strong>{{ __('Σημείωση:') }}</strong> {{ __('η εφαρμογή υποστηρίζει πολλαπλές εταιρείες (multi-tenant). Ως admin βλέπεις και διαχειρίζεσαι αποκλειστικά τη δική σου εταιρεία — ποτέ δεδομένα άλλων εταιρειών.') }}</p>
            <img src="{{ asset('img/kb/05-admin-dashboard.png') }}" class="kb-shot" alt="Admin dashboard">
        </div>
    </x-filament::section>

    <x-filament::section id="approve-reject" heading="{{ __('Έγκριση / Απόρριψη Αιτήσεων') }}" icon="heroicon-o-check-circle">
        <div class="kb-section">
            <p>{{ __('Στη λίστα "Αιτήσεις Άδειας" βλέπεις τις αιτήσεις όλων. Για κάθε εκκρεμή αίτηση έχεις δύο γρήγορα κουμπιά, χωρίς να χρειάζεται να ανοίξεις τίποτα:') }}</p>
            <img src="{{ asset('img/kb/06-admin-leave-requests.png') }}" class="kb-shot" alt="Admin leave requests list">
            <ul>
                <li><strong>{{ __('Έγκριση') }}</strong> — {{ __('επιβεβαιώνεις και η άδεια γίνεται οριστική. Ο υπάλληλος ειδοποιείται αμέσως.') }}</li>
                <li><strong>{{ __('Απόρριψη') }}</strong> — {{ __('ανοίγει ένα παράθυρο όπου γράφεις υποχρεωτικά την αιτία απόρριψης πριν επιβεβαιώσεις.') }}</li>
            </ul>
            <img src="{{ asset('img/kb/07-admin-reject-modal.png') }}" class="kb-shot" alt="Reject modal with reason field">
            <p>{{ __('Μπορείς να φιλτράρεις τη λίστα ανά κατάσταση (Εκκρεμεί/Εγκρίθηκε/Απορρίφθηκε/Ακυρώθηκε) από το φίλτρο πάνω δεξιά στον πίνακα.') }}</p>
        </div>
    </x-filament::section>

    <x-filament::section id="manage-users" heading="{{ __('Διαχείριση Χρηστών') }}" icon="heroicon-o-users">
        <div class="kb-section">
            <p>{{ __('Από το μενού "Χρήστες" δημιουργείς νέους υπαλλήλους ή admins. Τα σημαντικά πεδία είναι:') }}</p>
            <ul>
                <li><strong>{{ __('Ρόλος') }}</strong> — {{ __('Admin ή Υπάλληλος. Μόνο οι admins βλέπουν Χρήστες/Τύπους Αδειών/όλες τις αιτήσεις.') }}</li>
                <li><strong>{{ __('Ημερομηνία πρόσληψης') }}</strong> — {{ __('η ακριβής ημερομηνία που ξεκίνησε ΣΕ ΕΣΕΝΑ ως εργοδότη. Χρησιμοποιείται για τον αυτόματο υπολογισμό άδειας.') }}</li>
                <li><strong>{{ __('Προϋπηρεσία σε άλλους εργοδότες') }}</strong> — {{ __('μόνο τα χρόνια ΠΡΙΝ από αυτή τη θέση (όχι το άθροισμα). Το σύστημα το προσθέτει αυτόματα στα χρόνια εδώ για τα thresholds των 12/25 ετών του νόμου.') }}</li>
            </ul>
            <img src="{{ asset('img/kb/08-admin-users.png') }}" class="kb-shot" alt="Users list">
            <p>{{ __('Η λίστα δείχνει μόνο τους υπαλλήλους της δικής σου εταιρείας. Νέοι χρήστες που δημιουργείς εδώ ανήκουν αυτόματα σε αυτήν.') }}</p>
        </div>
    </x-filament::section>

    <x-filament::section id="leave-types" heading="{{ __('Τύποι Αδειών & Υπολογισμός Δικαιώματος') }}" icon="heroicon-o-adjustments-horizontal">
        <div class="kb-section">
            <p>{{ __('Από το μενού "Τύποι Αδειών" ελέγχεις πώς υπολογίζεται το δικαίωμα κάθε τύπου άδειας. Οι τύποι είναι δικοί σας — κάθε εταιρεία ξεκινάει με τους 3 βασικούς (Κανονική, Αναρρωτική, Άνευ Αποδοχών) και μπορεί να τους επεξεργαστεί ή να προσθέσει δικούς της, χωρίς να επηρεάζει άλλες εταιρείες. Υπάρχουν τρεις τρόποι υπολογισμού, ανά τύπο:') }}</p>
            <img src="{{ asset('img/kb/09-admin-leave-types.png') }}" class="kb-shot" alt="Leave types list">
            <ul>
                <li><strong>{{ __('Ελληνική Νομοθεσία (Α.Ν. 539/1945)') }}</strong> — {{ __('πλήρως αυτόματο: proration 1ου έτους, 21 μέρες στο 2ο, 22 από το 3ο, 25 στα 12 συνολικά χρόνια (σε οποιονδήποτε εργοδότη) ή 10 στον ίδιο εργοδότη, 26 στα 25 συνολικά.') }}</li>
                <li><strong>{{ __('Κανόνες Υπολογισμού (tiers)') }}</strong> — {{ __('δικά σου custom κλιμάκια βάσει ετών προϋπηρεσίας στον ίδιο εργοδότη (π.χ. 0-2 έτη = 18 μέρες, 3+ = 24 μέρες).') }}</li>
                <li><strong>{{ __('Σταθερές μέρες/έτος') }}</strong> — {{ __('ίδιος αριθμός ημερών για όλους, κάθε χρόνο (π.χ. Αναρρωτική Άδεια).') }}</li>
            </ul>
            <img src="{{ asset('img/kb/10-admin-leave-type-edit.png') }}" class="kb-shot" alt="Leave type edit form with Greek law toggle">
            <p>{{ __('Το πεδίο "Απαιτεί σημείωση/αιτιολογία" κάνει υποχρεωτικό το πεδίο σημείωσης όταν κάποιος υποβάλλει αυτόν τον τύπο άδειας (π.χ. λόγος αναρρωτικής).') }}</p>
        </div>
    </x-filament::section>

    <x-filament::section id="export-report" heading="{{ __('Εξαγωγή & Αναφορές PDF') }}" icon="heroicon-o-document-arrow-down">
        <div class="kb-section">
            <p>{{ __('Στην κορυφή της λίστας "Αιτήσεις Άδειας" έχεις:') }}</p>
            <ul>
                <li><strong>{{ __('Αναφορά Όλων (PDF)') }}</strong> — {{ __('όλες οι αιτήσεις όλων των υπαλλήλων σε ένα PDF, ταξινομημένες ανά υπάλληλο.') }}</li>
                <li><strong>{{ __('Εξαγωγή Excel') }}</strong> — {{ __('κατεβάζει τη (φιλτραρισμένη) λίστα αιτήσεων σε αρχείο .xlsx. Μπορείς να επιλέξεις συγκεκριμένες γραμμές πριν την εξαγωγή, ή να πάρεις όλη τη λίστα.') }}</li>
            </ul>
            <img src="{{ asset('img/kb/14-admin-export-buttons.png') }}" class="kb-shot" alt="Export buttons on admin leave requests list">
            <p>{{ __('Επιπλέον, από το μενού "Χρήστες", κάθε γραμμή έχει το δικό της κουμπί "Αναφορά PDF" — μια αναλυτική αναφορά (υπόλοιπο + ιστορικό) για έναν συγκεκριμένο υπάλληλο, χρήσιμη π.χ. για αξιολόγηση ή αρχειοθέτηση.') }}</p>
            <img src="{{ asset('img/kb/15-admin-users-pdf-button.png') }}" class="kb-shot" alt="Per-employee PDF report button on Users list">
        </div>
    </x-filament::section>

    <x-filament::section id="balance-override" heading="{{ __('Χειροκίνητη Ρύθμιση Υπολοίπου (Override)') }}" icon="heroicon-o-pencil-square">
        <div class="kb-section">
            <p>{{ __('Αν χρειαστεί να δώσεις σε συγκεκριμένο υπάλληλο διαφορετικό αριθμό ημερών από τον αυτόματο υπολογισμό (π.χ. έξτρα μέρες ως μπόνους, ή διόρθωση), άνοιξε τον χρήστη και πήγαινε στο tab "Χειροκίνητες Ρυθμίσεις Υπολοίπου".') }}</p>
            <img src="{{ asset('img/kb/11-admin-user-edit.png') }}" class="kb-shot" alt="User edit page with tabs">
            <p>{{ __('Πρόσθεσε μια εγγραφή με τύπο άδειας, έτος, και τον αριθμό ημερών που θέλεις. Αυτό αντικαθιστά ΠΛΗΡΩΣ τον αυτόματο υπολογισμό για αυτόν τον χρήστη/τύπο/έτος — δεν προστίθεται σε αυτόν, τον αντικαθιστά.') }}</p>
            <img src="{{ asset('img/kb/12-admin-balance-override.png') }}" class="kb-shot" alt="Manual balance override tab">
        </div>
    </x-filament::section>

    <x-filament::section id="reminders" heading="{{ __('Αυτόματες Υπενθυμίσεις') }}" icon="heroicon-o-clock">
        <div class="kb-section">
            <p>{{ __('Κάθε μέρα στις 08:00, το σύστημα ελέγχει αυτόματα ποιες εγκεκριμένες άδειες ξεκινάνε ή λήγουν την επόμενη μέρα, και στέλνει υπενθύμιση email + in-app notification στον υπάλληλο ΚΑΙ σε όλους τους admins.') }}</p>
        </div>
    </x-filament::section>

    </div>
</div>
</x-filament-panels::page>
