<x-filament-panels::page>
<style>
    .kb-shot { max-width: 100%; border-radius: 8px; border: 1px solid #e4e4e7; box-shadow: 0 1px 3px rgba(0,0,0,0.08); margin: 12px 0 4px 0; }
    .kb-section p { line-height: 1.65; margin: 0 0 12px 0; }
    .kb-section ol, .kb-section ul { line-height: 1.65; margin: 0 0 12px 0; padding-left: 22px; }
    .kb-section ol li, .kb-section ul li { margin-bottom: 6px; }
</style>

<div style="display: flex; flex-direction: column; gap: 24px;">

<x-filament::section heading="{{ __('Ο Πίνακας Ελέγχου σου') }}" icon="heroicon-o-home">
    <div class="kb-section">
        <p>{{ __('Μόλις συνδεθείς, βλέπεις τον προσωπικό σου Πίνακα Ελέγχου: κάρτες με το υπόλοιπο ημερών ανά τύπο άδειας, και από κάτω το ημερολόγιό σου.') }}</p>
        <img src="{{ asset('img/kb/01-employee-dashboard.png') }}" class="kb-shot" alt="Employee dashboard">
        <p>{{ __('Κάθε κάρτα δείχνει: πόσες μέρες σου έχουν μείνει / πόσες δικαιούσαι συνολικά φέτος, και πόσες έχεις ήδη χρησιμοποιήσει. Το υπόλοιπο υπολογίζεται αυτόματα βάσει της προϋπηρεσίας σου (ή έχει οριστεί χειροκίνητα από τον διαχειριστή).') }}</p>
        <p>{{ __('Στο ημερολόγιο βλέπεις μόνο τις δικές σου άδειες (εγκεκριμένες και εκκρεμείς).') }}</p>
    </div>
</x-filament::section>

<x-filament::section heading="{{ __('Πώς να υποβάλεις αίτηση άδειας') }}" icon="heroicon-o-document-plus">
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

<x-filament::section heading="{{ __('Οι Αιτήσεις σου') }}" icon="heroicon-o-clipboard-document-list">
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

<x-filament::section heading="{{ __('Ειδοποιήσεις') }}" icon="heroicon-o-bell">
    <div class="kb-section">
        <p>{{ __('Ενημερώνεσαι αυτόματα, μέσα στην εφαρμογή (κουδουνάκι πάνω δεξιά) και μέσω email, όταν:') }}</p>
        <ul>
            <li>{{ __('η αίτησή σου εγκρίνεται ή απορρίπτεται') }}</li>
            <li>{{ __('η άδειά σου ξεκινάει ή λήγει την επόμενη μέρα (υπενθύμιση)') }}</li>
        </ul>
    </div>
</x-filament::section>

<x-filament::section heading="{{ __('Γλώσσα και Λογαριασμός') }}" icon="heroicon-o-cog-6-tooth">
    <div class="kb-section">
        <p>{{ __('Δίπλα στο κουδουνάκι ειδοποιήσεων μπορείς να αλλάξεις γλώσσα (Ελληνικά/English) οποιαδήποτε στιγμή.') }}</p>
        <img src="{{ asset('img/kb/04-language-switcher.png') }}" class="kb-shot" alt="Language switcher">
        <p>{{ __('Από το μενού του λογαριασμού σου (πάνω δεξιά) μπορείς να επεξεργαστείς το προφίλ σου (όνομα, email, password). Αν ξεχάσεις τον κωδικό σου, χρησιμοποίησε το "Ξεχάσατε τον κωδικό σας;" στη σελίδα σύνδεσης.') }}</p>
    </div>
</x-filament::section>

</div>
</x-filament-panels::page>
