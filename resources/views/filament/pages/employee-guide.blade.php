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
        active: 'first-login',
        ids: ['first-login', 'dashboard', 'carryover', 'submit-request', 'partial-leave', 'your-requests', 'export-report', 'notifications', 'language-account', 'projects', 'board', 'task-details', 'task-activity'],
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
                <li><a href="#first-login" :class="active === 'first-login' && 'kb-toc-active'">1. {{ __('Η πρώτη σου σύνδεση') }}</a></li>
                <li><a href="#dashboard" :class="active === 'dashboard' && 'kb-toc-active'">2. {{ __('Ο Πίνακας Ελέγχου σου') }}</a></li>
                <li><a href="#carryover" :class="active === 'carryover' && 'kb-toc-active'">3. {{ __('Μέρες από το προηγούμενο έτος') }}</a></li>
                <li><a href="#submit-request" :class="active === 'submit-request' && 'kb-toc-active'">4. {{ __('Πώς να υποβάλεις αίτηση άδειας') }}</a></li>
                <li><a href="#partial-leave" :class="active === 'partial-leave' && 'kb-toc-active'">5. {{ __('Πώς να υποβάλεις μερική άδεια (μισή μέρα ή ώρες)') }}</a></li>
                <li><a href="#your-requests" :class="active === 'your-requests' && 'kb-toc-active'">6. {{ __('Οι Αιτήσεις σου') }}</a></li>
                <li><a href="#export-report" :class="active === 'export-report' && 'kb-toc-active'">7. {{ __('Εξαγωγή & Αναφορά PDF') }}</a></li>
                <li><a href="#notifications" :class="active === 'notifications' && 'kb-toc-active'">8. {{ __('Ειδοποιήσεις') }}</a></li>
                <li><a href="#language-account" :class="active === 'language-account' && 'kb-toc-active'">9. {{ __('Γλώσσα και Λογαριασμός') }}</a></li>
                <li style="margin-top: 10px; padding: 6px 12px; font-size: 12px; font-weight: 700; color: #a1a1aa; text-transform: uppercase; letter-spacing: .04em;">{{ __('Έργα') }}</li>
                <li><a href="#projects" :class="active === 'projects' && 'kb-toc-active'">10. {{ __('Έργα και Εργασίες') }}</a></li>
                <li><a href="#board" :class="active === 'board' && 'kb-toc-active'">11. {{ __('Ο Πίνακας του Έργου') }}</a></li>
                <li><a href="#task-details" :class="active === 'task-details' && 'kb-toc-active'">12. {{ __('Τι κρατάει μια εργασία') }}</a></li>
                <li><a href="#task-activity" :class="active === 'task-activity' && 'kb-toc-active'">13. {{ __('Σχόλια, Αρχεία και Χρονόμετρο') }}</a></li>
            </ul>
        </x-filament::section>
    </nav>

    <div style="display: flex; flex-direction: column; gap: 24px;">

    <x-filament::section id="first-login" heading="{{ __('Η πρώτη σου σύνδεση') }}" icon="heroicon-o-key">
        <div class="kb-section">
            <p>{{ __('Δεν δημιουργείς εσύ λογαριασμό. Ο διαχειριστής της εταιρείας σου σε προσθέτει, και εσύ λαμβάνεις email με έναν προσωπικό σύνδεσμο.') }}</p>
            <ol>
                <li>{{ __('Άνοιξε το email και πάτα το κουμπί της πρόσκλησης.') }}</li>
                <li>{{ __('Στη σελίδα που ανοίγει, όρισε τον κωδικό σου και επιβεβαίωσέ τον.') }}</li>
                <li>{{ __('Πατώντας "Αποθήκευση και σύνδεση" μπαίνεις κατευθείαν στην εφαρμογή — δεν χρειάζεται δεύτερη σύνδεση.') }}</li>
            </ol>
            <img src="{{ asset('img/kb/24-employee-accept-invitation.png') }}" class="kb-shot" alt="Set your password from the invitation link">
            <p>{{ __('Τον κωδικό τον ορίζεις μόνο εσύ — ο διαχειριστής δεν τον βλέπει και δεν μπορεί να τον ανακτήσει.') }}</p>
            <p><strong>{{ __('Ο σύνδεσμος ισχύει για 7 ημέρες και χρησιμοποιείται μία μόνο φορά.') }}</strong> {{ __('Αν αργήσεις ή αν δεις το μήνυμα "Ο σύνδεσμος δεν ισχύει", ζήτησε από τον διαχειριστή σου να πατήσει "Επαναποστολή πρόσκλησης". Θα λάβεις καινούριο email — ο παλιός σύνδεσμος παύει αμέσως να δουλεύει.') }}</p>
            <p>{{ __('Από εκεί και πέρα συνδέεσαι κανονικά με το email και τον κωδικό σου. Αν τον ξεχάσεις, χρησιμοποίησε το "Ξεχάσατε τον κωδικό σας;" στη σελίδα σύνδεσης.') }}</p>
            <img src="{{ asset('img/kb/00-login.png') }}" class="kb-shot" alt="Login screen">
        </div>
    </x-filament::section>

    <x-filament::section id="dashboard" heading="{{ __('Ο Πίνακας Ελέγχου σου') }}" icon="heroicon-o-home">
        <div class="kb-section">
            <p>{{ __('Μόλις συνδεθείς, βλέπεις τον προσωπικό σου Πίνακα Ελέγχου: κάρτες με το υπόλοιπο ημερών ανά τύπο άδειας, και από κάτω το ημερολόγιό σου.') }}</p>
            <img src="{{ asset('img/kb/01-employee-dashboard.png') }}" class="kb-shot" alt="Employee dashboard">
            <p>{{ __('Κάθε κάρτα δείχνει: πόσες μέρες σου έχουν μείνει / πόσες δικαιούσαι συνολικά φέτος, και πόσες έχεις ήδη χρησιμοποιήσει. Το υπόλοιπο υπολογίζεται αυτόματα βάσει της προϋπηρεσίας σου (ή έχει οριστεί χειροκίνητα από τον διαχειριστή).') }}</p>
            <p>{{ __('Στο ημερολόγιο βλέπεις μόνο τις δικές σου άδειες (εγκεκριμένες και εκκρεμείς).') }}</p>
            <p>{{ __('Ολόκληρη η εφαρμογή αφορά μόνο τη δική σου εταιρεία — δεν βλέπεις ποτέ συναδέλφους ή δεδομένα άλλης εταιρείας.') }}</p>
        </div>
    </x-filament::section>

    <x-filament::section id="carryover" heading="{{ __('Μέρες από το προηγούμενο έτος') }}" icon="heroicon-o-arrow-path">
        <div class="kb-section">
            <p>{{ __('Αν η εταιρεία σου το επιτρέπει, οι μέρες άδειας που δεν πρόλαβες να χρησιμοποιήσεις πέρσι μεταφέρονται στο νέο έτος — αλλά μόνο μέχρι μια συγκεκριμένη ημερομηνία (π.χ. 31 Μαρτίου).') }}</p>
            <p>{{ __('Στον Πίνακα Ελέγχου θα δεις γι\' αυτές ξεχωριστή κάρτα, με την ένδειξη πότε λήγουν. Δεν προστίθενται στο φετινό σου υπόλοιπο· εμφανίζονται χωριστά ακριβώς για να ξέρεις τι κινδυνεύεις να χάσεις.') }}</p>
            <img src="{{ asset('img/kb/25-employee-carryover-card.png') }}" class="kb-shot" alt="Carried-over days card on the dashboard">
            <ul>
                <li>{{ __('Όταν υποβάλλεις άδεια, χρησιμοποιούνται ΠΡΩΤΑ οι περσινές μέρες, αφού αυτές λήγουν.') }}</li>
                <li>{{ __('Μια αίτηση μπορεί να καλύψει και τα δύο: π.χ. αν σου έχουν μείνει 3 περσινές μέρες και ζητήσεις 5, θα πάρει 3 από πέρσι και 2 από φέτος.') }}</li>
                <li>{{ __('Μετά την ημερομηνία λήξης, οι περσινές μέρες παύουν να είναι διαθέσιμες και η κάρτα εξαφανίζεται.') }}</li>
            </ul>
        </div>
    </x-filament::section>

    <x-filament::section id="submit-request" heading="{{ __('Πώς να υποβάλεις αίτηση άδειας') }}" icon="heroicon-o-document-plus">
        <div class="kb-section">
            <ol>
                <li>{{ __('Πήγαινε στο μενού "Αιτήσεις Άδειας" και πάτα "Προσθήκη Αίτησης Άδειας".') }}</li>
                <li>{{ __('Επίλεξε τύπο άδειας (π.χ. Κανονική, Αναρρωτική).') }}</li>
                <li>{{ __('Επίλεξε ημερομηνία "Από" και "Έως" — οι εργάσιμες μέρες υπολογίζονται αυτόματα (Σαββατοκύριακα δεν προσμετρώνται).') }}</li>
                <li>{{ __('Αν ο τύπος άδειας το απαιτεί (π.χ. Αναρρωτική), θα εμφανιστεί πεδίο "Σημείωση" όπου γράφεις την αιτιολογία.') }}</li>
                <li>{{ __('Πάτα αποθήκευση. Η αίτηση μπαίνει σε κατάσταση "Εκκρεμεί" μέχρι να την εγκρίνει/απορρίψει ο διαχειριστής.') }}</li>
            </ol>
            <img src="{{ asset('img/kb/03-employee-create-request.png') }}" class="kb-shot" alt="Create leave request form">
            <p><strong>{{ __('Σημείωση:') }}</strong> {{ __('το σύστημα δεν σου επιτρέπει να υποβάλεις αίτηση που επικαλύπτεται με άλλη σου άδεια, ή που ξεπερνάει το διαθέσιμο υπόλοιπό σου — θα δεις μήνυμα σφάλματος αν συμβεί αυτό.') }}</p>
        </div>
    </x-filament::section>

    <x-filament::section id="partial-leave" heading="{{ __('Πώς να υποβάλεις μερική άδεια (μισή μέρα ή ώρες)') }}" icon="heroicon-o-clock">
        <div class="kb-section">
            <p>{{ __('Στο πεδίο "Τύπος Διάρκειας" μπορείς να επιλέξεις εκτός από "Ολόκληρη Μέρα":') }}</p>
            <ul>
                <li>{{ __('"Μισή Μέρα" — η άδεια αφορά μία μόνο ημερομηνία και μετράει 0.5 μέρα από το υπόλοιπό σου.') }}</li>
                <li>{{ __('"Ώρες" — επιλέγεις πόσες ώρες (έως 7.5) θα λείψεις μέσα σε μία ημερομηνία. Το σύστημα μετατρέπει αυτόματα τις ώρες σε ισοδύναμο κλάσμα ημέρας (με βάση 8ωρη εργάσιμη μέρα) για το υπόλοιπό σου.') }}</li>
            </ul>
            <p>{{ __('Και στις δύο περιπτώσεις, η ημερομηνία "Έως" ορίζεται αυτόματα ίδια με το "Από".') }}</p>
        </div>
    </x-filament::section>

    <x-filament::section id="your-requests" heading="{{ __('Οι Αιτήσεις σου') }}" icon="heroicon-o-clipboard-document-list">
        <div class="kb-section">
            <p>{{ __('Στη λίστα "Αιτήσεις Άδειας" βλέπεις όλες τις δικές σου αιτήσεις και την κατάστασή τους:') }}</p>
            <ul>
                <li>🟡 <strong>{{ __('Εκκρεμεί') }}</strong> — {{ __('περιμένει έγκριση από τον διαχειριστή. Μπορείς ακόμα να την επεξεργαστείς ή να τη διαγράψεις.') }}</li>
                <li>🟢 <strong>{{ __('Εγκρίθηκε') }}</strong> — {{ __('η άδεια είναι οριστική. Δεν μπορεί πλέον να επεξεργαστεί.') }}</li>
                <li>🔴 <strong>{{ __('Απορρίφθηκε') }}</strong> — {{ __('θα δεις και την αιτία απόρριψης που έγραψε ο διαχειριστής.') }}</li>
            </ul>
            <img src="{{ asset('img/kb/02-employee-leave-requests.png') }}" class="kb-shot" alt="Employee leave requests list">
        </div>
    </x-filament::section>

    <x-filament::section id="export-report" heading="{{ __('Εξαγωγή & Αναφορά PDF') }}" icon="heroicon-o-document-arrow-down">
        <div class="kb-section">
            <p>{{ __('Στην κορυφή της λίστας "Αιτήσεις Άδειας" έχεις δύο επιλογές για να πάρεις τα δεδομένα σου έξω από την εφαρμογή:') }}</p>
            <ul>
                <li><strong>{{ __('Η Αναφορά μου (PDF)') }}</strong> — {{ __('κατεβάζει ένα αρχείο PDF με το υπόλοιπο ημερών σου ανά τύπο άδειας και όλο το ιστορικό των αιτήσεών σου, έτοιμο για εκτύπωση ή αποστολή.') }}</li>
                <li><strong>{{ __('Εξαγωγή Excel') }}</strong> — {{ __('κατεβάζει τις δικές σου αιτήσεις σε αρχείο .xlsx, ανοίγει σε Excel/Google Sheets.') }}</li>
            </ul>
            <img src="{{ asset('img/kb/13-employee-export-buttons.png') }}" class="kb-shot" alt="Export buttons on employee leave requests list">
        </div>
    </x-filament::section>

    <x-filament::section id="notifications" heading="{{ __('Ειδοποιήσεις') }}" icon="heroicon-o-bell">
        <div class="kb-section">
            <p>{{ __('Ενημερώνεσαι αυτόματα, μέσα στην εφαρμογή (κουδουνάκι πάνω δεξιά) και μέσω email, όταν:') }}</p>
            <ul>
                <li>{{ __('η αίτησή σου εγκρίνεται ή απορρίπτεται') }}</li>
                <li>{{ __('η άδειά σου ξεκινάει ή λήγει την επόμενη μέρα (υπενθύμιση)') }}</li>
            </ul>
        </div>
    </x-filament::section>

    <x-filament::section id="language-account" heading="{{ __('Γλώσσα και Λογαριασμός') }}" icon="heroicon-o-cog-6-tooth">
        <div class="kb-section">
            <p>{{ __('Δίπλα στο κουδουνάκι ειδοποιήσεων μπορείς να αλλάξεις γλώσσα (Ελληνικά/English) οποιαδήποτε στιγμή.') }}</p>
            <img src="{{ asset('img/kb/04-language-switcher.png') }}" class="kb-shot" alt="Language switcher">
            <p>{{ __('Από το μενού του λογαριασμού σου (πάνω δεξιά) μπορείς να επεξεργαστείς το προφίλ σου (όνομα, email, password). Αν ξεχάσεις τον κωδικό σου, χρησιμοποίησε το "Ξεχάσατε τον κωδικό σας;" στη σελίδα σύνδεσης.') }}</p>
        </div>
    </x-filament::section>

    <x-filament::section id="projects" heading="{{ __('Έργα και Εργασίες') }}" icon="heroicon-o-folder">
        <div class="kb-section">
            <p>{{ __('Πέρα από τις άδειες, η εφαρμογή κρατάει και τη δουλειά της εταιρείας σου: έργα, και μέσα σε αυτά εργασίες.') }}</p>
            <p>{{ __('Στο πλαϊνό μενού, κάτω από τα "Έργα", υπάρχει ένα στοιχείο για κάθε έργο της εταιρείας. Πατώντας το ανοίγει κατευθείαν ο πίνακας εκείνου του έργου — δεν χρειάζεται να περάσεις από ενδιάμεση λίστα.') }}</p>
            <img src="{{ asset('img/kb/26-projects-list.png') }}" class="kb-shot" alt="Projects in the sidebar">
            <p>{{ __('Το "Όλα τα έργα" ανοίγει τη συνολική λίστα, με τα αρχειοθετημένα έργα και τον αριθμό εργασιών του καθενός.') }}</p>
            <p>{{ __('Δεν δημιουργείς εσύ έργα — αυτό το κάνει ο διαχειριστής. Μπορείς όμως να δημιουργήσεις εργασία, να την επεξεργαστείς και να τη μετακινήσεις μέσα στον πίνακα.') }}</p>
        </div>
    </x-filament::section>

    <x-filament::section id="board" heading="{{ __('Ο Πίνακας του Έργου') }}" icon="heroicon-o-view-columns">
        <div class="kb-section">
            <p>{{ __('Πατώντας ένα έργο ανοίγει ο πίνακάς του — οι εργασίες σε στήλες, ανάλογα με το πού βρίσκονται.') }}</p>
            <img src="{{ asset('img/kb/27-project-board.png') }}" class="kb-shot" alt="Project board">
            <p><strong>{{ __('Σύρε μια κάρτα') }}</strong> {{ __('από στήλη σε στήλη για να αλλάξεις την κατάστασή της, ή πάνω-κάτω μέσα στην ίδια στήλη για να αλλάξεις σειρά. Η αλλαγή αποθηκεύεται αμέσως — δεν υπάρχει κουμπί αποθήκευσης.') }}</p>
            <p><strong>{{ __('Πατώντας μια κάρτα') }}</strong> {{ __('ανοίγει πλαϊνό παράθυρο με τα στοιχεία της, χωρίς να φύγεις από τον πίνακα. Αλλάζεις ό,τι χρειάζεται, πατάς Αποθήκευση και η κάρτα ενημερώνεται επιτόπου.') }}</p>
            <p>{{ __('Στο ίδιο παράθυρο βλέπεις και τα συνημμένα της εργασίας — οι εικόνες με μικρογραφία — και μπορείς να ανεβάσεις καινούρια. Για σχόλια, χρονόμετρο και διαγραφή αρχείων, το κουμπί "Πλήρης σελίδα" ανοίγει ολόκληρη την εργασία.') }}</p>
            <p><strong>{{ __('Νέα εργασία:') }}</strong> {{ __('το "+" δίπλα στο όνομα κάθε στήλης δημιουργεί εργασία απευθείας μέσα σε αυτήν. Υπάρχει και κουμπί "Νέα Εργασία" πάνω δεξιά, που την τοποθετεί στην αρχική στήλη.') }}</p>
            <p><strong>{{ __('Οι στήλες δεν είναι ίδιες παντού.') }}</strong> {{ __('Κάθε έργο έχει τις δικές του, γιατί μια ομάδα πωλήσεων και μια ομάδα ανάπτυξης δεν δουλεύουν με τα ίδια στάδια.') }}</p>
            <img src="{{ asset('img/kb/28-project-board-custom-columns.png') }}" class="kb-shot" alt="A board with its own columns">
            <p>{{ __('Όταν μια κάρτα φτάσει στη στήλη που η εταιρεία έχει ορίσει ως "ολοκληρωμένη", η εργασία σημειώνεται αυτόματα ως ολοκληρωμένη. Αν την τραβήξεις πίσω, η σήμανση αφαιρείται.') }}</p>
        </div>
    </x-filament::section>

    <x-filament::section id="task-details" heading="{{ __('Τι κρατάει μια εργασία') }}" icon="heroicon-o-clipboard-document">
        <div class="kb-section">
            <p>{{ __('Ανοίγοντας μια εργασία βρίσκεις τα βασικά — τίτλος, έργο, στήλη, ανάθεση, προτεραιότητα, ημερομηνίες, περιγραφή.') }}</p>
            <img src="{{ asset('img/kb/34-task-form.png') }}" class="kb-shot" alt="Task form with additional fields">
            <p><strong>{{ __('Πρόσθετα Πεδία:') }}</strong> {{ __('από κάτω μπορεί να υπάρχουν πεδία που έφτιαξε η εταιρεία σου — π.χ. εκτίμηση ωρών, ομάδα, αξία συμβολαίου. Ποια εμφανίζονται εξαρτάται από το έργο, οπότε αν αλλάξεις έργο αλλάζουν και τα πεδία. Όσα έχουν αστερίσκο είναι υποχρεωτικά.') }}</p>
            <p>{{ __('Στο κάτω μέρος της σελίδας υπάρχουν καρτέλες για τα σχόλια, τα συνημμένα και τον χρόνο.') }}</p>
        </div>
    </x-filament::section>

    <x-filament::section id="task-activity" heading="{{ __('Σχόλια, Αρχεία και Χρονόμετρο') }}" icon="heroicon-o-clock">
        <div class="kb-section">
            <p><strong>{{ __('Σχόλια') }}</strong> — {{ __('γράφεις ό,τι χρειάζεται να ξέρει η ομάδα για την εργασία. Μπορείς να επεξεργαστείς ή να σβήσεις μόνο τα δικά σου.') }}</p>
            <img src="{{ asset('img/kb/35-task-comments.png') }}" class="kb-shot" alt="Task with comments and attachments">
            <p><strong>{{ __('Συνημμένα') }}</strong> — {{ __('ανεβάζεις αρχεία και εικόνες. Οι εικόνες δείχνουν μικρογραφία, και με το "Άνοιγμα" κατεβάζεις ή βλέπεις το αρχείο.') }}</p>
            <p>{{ __('Τα αρχεία δεν είναι δημόσια: ακόμα και με τον σύνδεσμο, τα βλέπει μόνο κάποιος συνδεδεμένος στη δική σου εταιρεία.') }}</p>
            <p><strong>{{ __('Χρονόμετρο') }}</strong> — {{ __('αν το έργο το έχει ενεργοποιημένο, πάνω δεξιά υπάρχει "Έναρξη χρονομέτρησης". Πατάς όταν ξεκινάς, "Διακοπή" όταν σταματάς, και ο χρόνος καταγράφεται.') }}</p>
            <img src="{{ asset('img/kb/36-task-time-log.png') }}" class="kb-shot" alt="Time log for a task">
            <p><strong>{{ __('Ένα χρονόμετρο τη φορά:') }}</strong> {{ __('αν ξεκινήσεις χρονόμετρο σε άλλη εργασία, το προηγούμενο σταματάει μόνο του και η εφαρμογή σου το λέει. Δεν μπορείς να χρεώνεις δύο εργασίες ταυτόχρονα.') }}</p>
            <p>{{ __('Στην καρτέλα "Καταγραφή Χρόνου" βλέπεις ποιος δούλεψε πότε και για πόσο. Μπορείς να σβήσεις μόνο τις δικές σου εγγραφές.') }}</p>
        </div>
    </x-filament::section>

    </div>
</div>
</x-filament-panels::page>
