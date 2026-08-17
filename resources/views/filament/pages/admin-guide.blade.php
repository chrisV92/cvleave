<x-filament-panels::page>
<style>
    .kb-shot { max-width: 100%; border-radius: 8px; border: 1px solid #e4e4e7; box-shadow: 0 1px 3px rgba(0,0,0,0.08); margin: 12px 0 4px 0; }
    .kb-section p { line-height: 1.65; margin: 0 0 12px 0; }
    .kb-section ol, .kb-section ul { line-height: 1.65; margin: 0 0 12px 0; padding-left: 22px; }
    .kb-section ol li, .kb-section ul li { margin-bottom: 6px; }
</style>

<div style="display: flex; flex-direction: column; gap: 24px;">

<x-filament::section heading="{{ __('Ο Πίνακας Ελέγχου του Admin') }}" icon="heroicon-o-home">
    <div class="kb-section">
        <p>{{ __('Ως admin βλέπεις το δικό σου προσωπικό υπόλοιπο ημερών στις κάρτες, αλλά το ημερολόγιο από κάτω δείχνει τις άδειες όλων των υπαλλήλων — όχι μόνο τις δικές σου.') }}</p>
        <img src="{{ asset('img/kb/05-admin-dashboard.png') }}" class="kb-shot" alt="Admin dashboard">
    </div>
</x-filament::section>

<x-filament::section heading="{{ __('Έγκριση / Απόρριψη Αιτήσεων') }}" icon="heroicon-o-check-circle">
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

<x-filament::section heading="{{ __('Διαχείριση Χρηστών') }}" icon="heroicon-o-users">
    <div class="kb-section">
        <p>{{ __('Από το μενού "Χρήστες" δημιουργείς νέους υπαλλήλους ή admins. Τα σημαντικά πεδία είναι:') }}</p>
        <ul>
            <li><strong>{{ __('Ρόλος') }}</strong> — {{ __('Admin ή Υπάλληλος. Μόνο οι admins βλέπουν Χρήστες/Τύπους Αδειών/όλες τις αιτήσεις.') }}</li>
            <li><strong>{{ __('Ημερομηνία πρόσληψης') }}</strong> — {{ __('η ακριβής ημερομηνία που ξεκίνησε ΣΕ ΕΣΕΝΑ ως εργοδότη. Χρησιμοποιείται για τον αυτόματο υπολογισμό άδειας.') }}</li>
            <li><strong>{{ __('Προϋπηρεσία σε άλλους εργοδότες') }}</strong> — {{ __('μόνο τα χρόνια ΠΡΙΝ από αυτή τη θέση (όχι το άθροισμα). Το σύστημα το προσθέτει αυτόματα στα χρόνια εδώ για τα thresholds των 12/25 ετών του νόμου.') }}</li>
        </ul>
        <img src="{{ asset('img/kb/08-admin-users.png') }}" class="kb-shot" alt="Users list">
    </div>
</x-filament::section>

<x-filament::section heading="{{ __('Τύποι Αδειών & Υπολογισμός Δικαιώματος') }}" icon="heroicon-o-adjustments-horizontal">
    <div class="kb-section">
        <p>{{ __('Από το μενού "Τύποι Αδειών" ελέγχεις πώς υπολογίζεται το δικαίωμα κάθε τύπου άδειας. Υπάρχουν τρεις τρόποι, ανά τύπο:') }}</p>
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

<x-filament::section heading="{{ __('Χειροκίνητη Ρύθμιση Υπολοίπου (Override)') }}" icon="heroicon-o-pencil-square">
    <div class="kb-section">
        <p>{{ __('Αν χρειαστεί να δώσεις σε συγκεκριμένο υπάλληλο διαφορετικό αριθμό ημερών από τον αυτόματο υπολογισμό (π.χ. έξτρα μέρες ως μπόνους, ή διόρθωση), άνοιξε τον χρήστη και πήγαινε στο tab "Χειροκίνητες Ρυθμίσεις Υπολοίπου".') }}</p>
        <img src="{{ asset('img/kb/11-admin-user-edit.png') }}" class="kb-shot" alt="User edit page with tabs">
        <p>{{ __('Πρόσθεσε μια εγγραφή με τύπο άδειας, έτος, και τον αριθμό ημερών που θέλεις. Αυτό αντικαθιστά ΠΛΗΡΩΣ τον αυτόματο υπολογισμό για αυτόν τον χρήστη/τύπο/έτος — δεν προστίθεται σε αυτόν, τον αντικαθιστά.') }}</p>
        <img src="{{ asset('img/kb/12-admin-balance-override.png') }}" class="kb-shot" alt="Manual balance override tab">
    </div>
</x-filament::section>

<x-filament::section heading="{{ __('Αυτόματες Υπενθυμίσεις') }}" icon="heroicon-o-clock">
    <div class="kb-section">
        <p>{{ __('Κάθε μέρα στις 08:00, το σύστημα ελέγχει αυτόματα ποιες εγκεκριμένες άδειες ξεκινάνε ή λήγουν την επόμενη μέρα, και στέλνει υπενθύμιση email + in-app notification στον υπάλληλο ΚΑΙ σε όλους τους admins.') }}</p>
    </div>
</x-filament::section>

</div>
</x-filament-panels::page>
