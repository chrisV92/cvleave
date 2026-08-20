<x-filament-panels::page>
<style>
    html { scroll-behavior: smooth; }
    .kb-shot { max-width: 100%; border-radius: 8px; border: 1px solid #e4e4e7; box-shadow: 0 1px 3px rgba(0,0,0,0.08); margin: 12px 0 4px 0; }
    .kb-section p { line-height: 1.65; margin: 0 0 12px 0; }
    .kb-section ol, .kb-section ul { line-height: 1.65; margin: 0 0 12px 0; padding-left: 22px; }
    .kb-section ol li, .kb-section ul li { margin-bottom: 6px; }
    .kb-layout { display: grid; grid-template-columns: 240px 1fr; gap: 24px; align-items: start; }
    /* The list scrolls on its own, not the page.
       Without a height limit a long guide pushes its last entries below the
       fold of a sticky column, and the only way to reach them is to scroll the
       article to its very end — which defeats the point of a table of contents.
       The card heading stays put because the limit sits on the list, not the
       card. */
    .kb-toc { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: 2px;
              max-height: calc(100vh - 240px); overflow-y: auto; overscroll-behavior: contain; }
    .kb-toc::-webkit-scrollbar { width: 6px; }
    .kb-toc::-webkit-scrollbar-thumb { background: #d4d4d8; border-radius: 3px; }
    .kb-toc { scrollbar-width: thin; scrollbar-color: #d4d4d8 transparent; }
    .kb-toc a { display: block; padding: 8px 12px; border-radius: 8px; text-decoration: none; color: #71717a; font-weight: 600; font-size: 14px; border-left: 3px solid transparent; }
    .kb-toc a:hover { background-color: #fafafa; color: #4f46e5; }
    .kb-toc a.kb-toc-active { background-color: #eef2ff; color: #4f46e5; border-left-color: #6366f1; }
    [id] { scroll-margin-top: 100px; }
    @media (max-width: 900px) {
        .kb-layout { grid-template-columns: 1fr; }
        .kb-nav-sticky { position: static !important; }
        /* Stacked above the article, the list has the whole page to use. */
        .kb-toc { max-height: none; overflow-y: visible; }
    }
</style>

<div
    x-data="{
        active: 'overview',
        ids: ['overview', 'modules', 'dashboard', 'tenants', 'users', 'impersonation', 'impersonation-log', 'access'],
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

            // Keep the highlighted entry inside the scrolled list, or the
            // reader loses their place in exactly the guides long enough to
            // need scrolling. 'nearest' moves it the least amount possible.
            this.$watch('active', (id) => {
                const link = this.$root.querySelector('.kb-toc a[href=\'#' + id + '\']');
                if (link) link.scrollIntoView({ block: 'nearest' });
            });
        },
    }"
    class="kb-layout"
>
    <nav class="kb-nav-sticky" style="position: sticky; top: 90px;">
        <x-filament::section heading="{{ __('Γρήγορη Πλοήγηση') }}" icon="heroicon-o-list-bullet">
            <ul class="kb-toc">
                <li><a href="#overview" :class="active === 'overview' && 'kb-toc-active'">1. {{ __('Τι είναι το Platform Panel') }}</a></li>
                <li><a href="#modules" :class="active === 'modules' && 'kb-toc-active'">2. {{ __('Τι περιλαμβάνει κάθε εταιρεία') }}</a></li>
                <li><a href="#dashboard" :class="active === 'dashboard' && 'kb-toc-active'">3. {{ __('Ο Πίνακας Ελέγχου') }}</a></li>
                <li><a href="#tenants" :class="active === 'tenants' && 'kb-toc-active'">4. {{ __('Δημιουργία Εταιρείας') }}</a></li>
                <li><a href="#users" :class="active === 'users' && 'kb-toc-active'">5. {{ __('Χρήστες όλων των εταιρειών') }}</a></li>
                <li><a href="#impersonation" :class="active === 'impersonation' && 'kb-toc-active'">6. {{ __('Είσοδος ως χρήστης') }}</a></li>
                <li><a href="#impersonation-log" :class="active === 'impersonation-log' && 'kb-toc-active'">7. {{ __('Ιστορικό Impersonation') }}</a></li>
                <li><a href="#access" :class="active === 'access' && 'kb-toc-active'">8. {{ __('Ποιος έχει πρόσβαση εδώ') }}</a></li>
            </ul>
        </x-filament::section>
    </nav>

    <div style="display: flex; flex-direction: column; gap: 24px;">

    <x-filament::section id="overview" heading="{{ __('Τι είναι το Platform Panel') }}" icon="heroicon-o-globe-alt">
        <div class="kb-section">
            <p>{{ __('Η εφαρμογή έχει δύο εντελώς ξεχωριστά panels:') }}</p>
            <ul>
                <li><strong>{{ __('/admin/{εταιρεία}') }}</strong> — {{ __('το panel κάθε εταιρείας-πελάτη. Ο διαχειριστής της βλέπει μόνο τους δικούς της υπαλλήλους, τύπους άδειας και αιτήσεις.') }}</li>
                <li><strong>{{ __('/platform') }}</strong> — {{ __('αυτό εδώ. Το επίπεδο πάνω από όλα, για εσένα ως ιδιοκτήτη της πλατφόρμας. Βλέπεις όλες τις εταιρείες μαζί.') }}</li>
            </ul>
            <p>{{ __('Ο διαχωρισμός είναι απόλυτος: καμία εταιρεία δεν μπορεί να δει δεδομένα άλλης, ούτε κατά λάθος ούτε αλλάζοντας το URL. Το Platform panel είναι το μοναδικό σημείο απ\' όπου φαίνεται η συνολική εικόνα.') }}</p>
        </div>
    </x-filament::section>

    <x-filament::section id="modules" heading="{{ __('Τι περιλαμβάνει κάθε εταιρεία') }}" icon="heroicon-o-squares-2x2">
        <div class="kb-section">
            <p>{{ __('Η πλατφόρμα δεν είναι πια μόνο άδειες. Κάθε εταιρεία παίρνει δύο ενότητες:') }}</p>
            <ul>
                <li><strong>{{ __('Άδειες') }}</strong> — {{ __('αιτήσεις, έγκριση, τύποι αδειών με υπολογισμό κατά Α.Ν. 539/1945, μεταφορά υπολοίπου, αναφορές.') }}</li>
                <li><strong>{{ __('Έργα') }}</strong> — {{ __('έργα με πίνακα kanban, εργασίες, πεδία που ορίζει η ίδια η εταιρεία, χρονομέτρηση, συνημμένα και σχόλια.') }}</li>
            </ul>
            <p>{{ __('Και οι δύο είναι απόλυτα διαχωρισμένες ανά εταιρεία, όπως όλα τα υπόλοιπα. Ένα έργο, μια εργασία ή ένα συνημμένο ανήκουν σε μία εταιρεία και δεν φαίνονται πουθενά αλλού.') }}</p>
            <p>{{ __('Δεν υπάρχει διαχείριση έργων από εδώ: τα έργα τα στήνει κάθε εταιρεία μόνη της. Αν χρειαστεί να δεις τι έχει στήσει μια εταιρεία, χρησιμοποίησε το impersonation — βλέπεις ακριβώς τον πίνακά της.') }}</p>
            <img src="{{ asset('img/kb/27-project-board.png') }}" class="kb-shot" alt="A company's project board seen through impersonation">
            <p><strong>{{ __('Προσοχή στα συνημμένα:') }}</strong> {{ __('όσο βρίσκεσαι σε impersonation μπορείς να ανοίξεις αρχεία που ανέβασαν εργαζόμενοι. Ισχύει ό,τι και για τα υπόλοιπα προσωπικά δεδομένα — μόνο για πραγματικό αίτημα υποστήριξης.') }}</p>
        </div>
    </x-filament::section>

    <x-filament::section id="dashboard" heading="{{ __('Ο Πίνακας Ελέγχου') }}" icon="heroicon-o-chart-bar">
        <div class="kb-section">
            <p>{{ __('Η αρχική σελίδα του Platform panel σου δίνει την κατάσταση της πλατφόρμας με μια ματιά.') }}</p>
            <img src="{{ asset('img/kb/20-platform-dashboard.png') }}" class="kb-shot" alt="Platform dashboard with stats, growth chart and tenant activity">
            <p>{{ __('Επάνω, τέσσερις μετρητές:') }}</p>
            <ul>
                <li><strong>{{ __('Εταιρείες') }}</strong> — {{ __('πόσες εταιρείες υπάρχουν συνολικά, και πόσες προστέθηκαν αυτόν τον μήνα.') }}</li>
                <li><strong>{{ __('Χρήστες') }}</strong> — {{ __('το σύνολο των λογαριασμών σε όλες τις εταιρείες μαζί.') }}</li>
                <li><strong>{{ __('Εκκρεμείς προσκλήσεις') }}</strong> — {{ __('πόσοι έχουν λάβει πρόσκληση αλλά δεν έχουν ορίσει ακόμα κωδικό. Μεγάλος αριθμός εδώ συνήθως σημαίνει ότι τα emails δεν φτάνουν — αξίζει έλεγχο.') }}</li>
                <li><strong>{{ __('Αιτήσεις άδειας') }}</strong> — {{ __('πόσες υποβλήθηκαν φέτος συνολικά, και πόσες περιμένουν ακόμα έγκριση.') }}</li>
            </ul>
            <p>{{ __('Από κάτω, το γράφημα "Νέες εταιρείες ανά μήνα" δείχνει την ανάπτυξη των τελευταίων 12 μηνών, και ο πίνακας "Δραστηριότητα ανά εταιρεία" δείχνει για κάθε εταιρεία πόσους χρήστες έχει, πόσες αιτήσεις άδειας έχουν καταχωρηθεί, και πότε έγινε η τελευταία κίνηση.') }}</p>
            <p>{{ __('Η στήλη "Τελευταία δραστηριότητα" είναι η πιο χρήσιμη: μια εταιρεία που δεν εμφανίζει καμία κίνηση για εβδομάδες πιθανότατα δεν χρησιμοποιεί πραγματικά την εφαρμογή.') }}</p>
        </div>
    </x-filament::section>

    <x-filament::section id="tenants" heading="{{ __('Δημιουργία Εταιρείας') }}" icon="heroicon-o-building-office-2">
        <div class="kb-section">
            <p>{{ __('Στο μενού "Εταιρείες" βλέπεις όλες τις εταιρείες-πελάτες, με αριθμό χρηστών και τύπων άδειας η καθεμία.') }}</p>
            <img src="{{ asset('img/kb/16-platform-tenants.png') }}" class="kb-shot" alt="Platform tenants list">
            <p>{{ __('Με το "Προσθήκη Εταιρείας" συμπληρώνεις δύο ομάδες πεδίων:') }}</p>
            <ol>
                <li><strong>{{ __('Όνομα και Slug') }}</strong> — {{ __('το slug είναι το αναγνωριστικό της στο URL, π.χ. /admin/peachpal. Πρέπει να είναι μοναδικό και δεν αλλάζει εύκολα μετά, γιατί όλοι οι σύνδεσμοι της εταιρείας το περιέχουν.') }}</li>
                <li><strong>{{ __('Πρώτος Admin') }}</strong> — {{ __('όνομα, email και κωδικός του ατόμου που θα διαχειρίζεται την εταιρεία.') }}</li>
            </ol>
            <img src="{{ asset('img/kb/17-platform-tenant-create.png') }}" class="kb-shot" alt="Create tenant form with first admin section">
            <p>{{ __('Ο πρώτος admin δεν είναι προαιρετικός, και αυτό είναι σκόπιμο: μια εταιρεία χωρίς κανέναν διαχειριστή είναι ένα κέλυφος στο οποίο δεν μπορεί να μπει κανείς. Δημιουργώντας τον μαζί με την εταιρεία, ο πελάτης μπορεί να συνδεθεί αμέσως.') }}</p>
            <p>{{ __('Μόλις πατήσεις αποθήκευση, το σύστημα στήνει μόνο του:') }}</p>
            <ul>
                <li>{{ __('τους 3 βασικούς τύπους άδειας (Κανονική, Αναρρωτική, Άνευ Αποδοχών)') }}</li>
                <li>{{ __('τους ρόλους Admin και Υπάλληλος για τη συγκεκριμένη εταιρεία') }}</li>
                <li>{{ __('τον λογαριασμό του πρώτου admin, με τον ρόλο Admin ήδη ανατεθειμένο') }}</li>
            </ul>
            <p>{{ __('Δεν χρειάζεται να ρυθμίσεις τίποτα άλλο. Ο admin της εταιρείας προσαρμόζει μετά τους τύπους άδειας, προσθέτει δικούς του και προσκαλεί το προσωπικό του από το δικό του panel.') }}</p>
        </div>
    </x-filament::section>

    <x-filament::section id="users" heading="{{ __('Χρήστες όλων των εταιρειών') }}" icon="heroicon-o-users">
        <div class="kb-section">
            <p>{{ __('Στο μενού "Χρήστες" βλέπεις τους χρήστες όλων των εταιρειών μαζί, με στήλη "Εταιρεία" και φίλτρο για να περιορίσεις τη λίστα σε μία συγκεκριμένη.') }}</p>
            <img src="{{ asset('img/kb/18-platform-users.png') }}" class="kb-shot" alt="Platform users list across tenants">
            <p>{{ __('Η στήλη "Πρόσκληση" δείχνει ποιοι δεν έχουν ορίσει ακόμα κωδικό: "Εκκρεμεί" όσο ο σύνδεσμος ισχύει, "Έληξε" μετά τις 7 ημέρες. Με την ενέργεια "Επαναποστολή πρόσκλησης" στέλνεις νέο σύνδεσμο — ο προηγούμενος ακυρώνεται αμέσως.') }}</p>
            <p>{{ __('Όταν δημιουργείς νέο χρήστη εδώ, επιλέγεις υποχρεωτικά σε ποια Εταιρεία θα ανήκει και τον Ρόλο του μέσα σε αυτήν.') }}</p>
            <img src="{{ asset('img/kb/19-platform-user-create.png') }}" class="kb-shot" alt="Create user form with tenant and role selects">
            <p><strong>{{ __('Σημείωση:') }}</strong> {{ __('ο ρόλος ενός χρήστη είναι πάντα σχετικός με τη συγκεκριμένη εταιρεία του. Το ίδιο πρόσωπο θα μπορούσε θεωρητικά να είναι Admin στη μία και Υπάλληλος σε άλλη — δεν υπάρχει έννοια καθολικού admin, πέρα από το platform flag παρακάτω.') }}</p>
        </div>
    </x-filament::section>

    <x-filament::section id="impersonation" heading="{{ __('Είσοδος ως χρήστης (Impersonation)') }}" icon="heroicon-o-arrow-right-on-rectangle">
        <div class="kb-section">
            <p>{{ __('Όταν ένας πελάτης σου λέει «δεν μου εμφανίζεται σωστά το υπόλοιπο», η περιγραφή σπάνια αρκεί. Με την ενέργεια "Είσοδος ως" στη λίστα Χρηστών μπαίνεις στην εφαρμογή ως αυτός ο χρήστης και βλέπεις ακριβώς την ίδια οθόνη με εκείνον.') }}</p>
            <img src="{{ asset('img/kb/21-platform-impersonate.png') }}" class="kb-shot" alt="Impersonate action on the platform users list">
            <p>{{ __('Μεταφέρεσαι αυτόματα στο panel της δικής του εταιρείας, με τα δικά του δικαιώματα. Μια μπάρα στην κορυφή της οθόνης σου θυμίζει ότι είσαι σε impersonation και σου δίνει κουμπί επιστροφής στον λογαριασμό σου.') }}</p>
            <p><strong>{{ __('Όρια ασφαλείας:') }}</strong></p>
            <ul>
                <li>{{ __('Μόνο platform admins μπορούν να κάνουν impersonation.') }}</li>
                <li>{{ __('Κανείς δεν μπορεί να μπει ως άλλος platform admin — αποτρέπει την κλιμάκωση δικαιωμάτων.') }}</li>
                <li>{{ __('Ό,τι κάνεις όσο είσαι μέσα καταγράφεται στο όνομα εκείνου του χρήστη. Χρησιμοποίησέ το για διάγνωση, όχι για να κάνεις αλλαγές στη θέση του.') }}</li>
            </ul>
            <p><strong>{{ __('Θέμα ιδιωτικότητας:') }}</strong> {{ __('το impersonation σου δίνει πρόσβαση σε προσωπικά δεδομένα εργαζομένων (άδειες, αιτιολογίες αναρρωτικών). Χρησιμοποίησέ το μόνο όταν υπάρχει πραγματικό αίτημα υποστήριξης, και ενημέρωσε τον πελάτη ότι η δυνατότητα υπάρχει.') }}</p>
        </div>
    </x-filament::section>

    <x-filament::section id="impersonation-log" heading="{{ __('Ιστορικό Impersonation') }}" icon="heroicon-o-clipboard-document-check">
        <div class="kb-section">
            <p>{{ __('Κάθε είσοδος και έξοδος από impersonation καταγράφεται αυτόματα. Στο μενού "Ιστορικό Impersonation" βλέπεις ποιος platform admin μπήκε ως ποιος χρήστης, σε ποια εταιρεία, πότε ξεκίνησε και πότε έληξε η συνεδρία.') }}</p>
            <img src="{{ asset('img/kb/22-platform-impersonation-log.png') }}" class="kb-shot" alt="Impersonation log list">
            <p>{{ __('Οι εγγραφές που δείχνουν "— σε εξέλιξη —" στη στήλη "Έληξε" είναι ενεργές συνεδρίες αυτή τη στιγμή.') }}</p>
            <p>{{ __('Ο πίνακας είναι αποκλειστικά για ανάγνωση — δεν διαγράφεται και δεν επεξεργάζεται από το UI. Αυτό είναι το ζητούμενο: ένα αρχείο ελέγχου που μπορεί κάποιος να αλλάξει δεν έχει καμία αξία αν χρειαστεί να αποδείξεις σε πελάτη ποιος και πότε είδε τα δεδομένα του.') }}</p>
        </div>
    </x-filament::section>

    <x-filament::section id="access" heading="{{ __('Ποιος έχει πρόσβαση εδώ') }}" icon="heroicon-o-shield-check">
        <div class="kb-section">
            <p>{{ __('Η πρόσβαση στο /platform ελέγχεται από ένα ξεχωριστό flag στον χρήστη ("Platform Admin"), εντελώς ανεξάρτητο από τους ρόλους μέσα στις εταιρείες.') }}</p>
            <ul>
                <li>{{ __('Ένας admin εταιρείας ΔΕΝ βλέπει το Platform panel.') }}</li>
                <li>{{ __('Ένας platform admin ΔΕΝ αποκτά αυτόματα ρόλο admin σε κάποια εταιρεία.') }}</li>
            </ul>
            <p>{{ __('Ο λόγος που είναι ξεχωριστό: οι ρόλοι Admin/Υπάλληλος αποθηκεύονται ανά εταιρεία, οπότε θα μπορούσαν θεωρητικά να ανατεθούν από κάποιον διαχειριστή εταιρείας. Η πρόσβαση στην πλατφόρμα δεν πρέπει ποτέ να εξαρτάται από κάτι που μπορεί να αλλάξει ένας πελάτης.') }}</p>
            <p>{{ __('Το flag ενεργοποιείται μόνο τεχνικά, απευθείας στη βάση — δεν υπάρχει επίτηδες κουμπί στο UI. Κράτησέ το σε ελάχιστους λογαριασμούς εμπιστοσύνης.') }}</p>
        </div>
    </x-filament::section>

    </div>
</div>
</x-filament-panels::page>
