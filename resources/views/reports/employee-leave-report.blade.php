<!DOCTYPE html>
<html lang="el">
<head>
<meta charset="utf-8">
<style>
    body { font-family: "DejaVu Sans", sans-serif; font-size: 11px; color: #27272a; }
    .header { border-bottom: 3px solid #d97706; padding-bottom: 12px; margin-bottom: 20px; }
    .brand { font-size: 20px; font-weight: bold; color: #b45309; margin: 0; }
    .subtitle { font-size: 12px; color: #71717a; margin: 4px 0 0 0; }
    .meta { margin-bottom: 20px; }
    .meta td { padding: 2px 0; }
    .meta .label { color: #71717a; width: 140px; }
    .meta .value { font-weight: bold; }
    h2 { font-size: 14px; color: #18181b; border-bottom: 1px solid #e4e4e7; padding-bottom: 6px; margin-top: 28px; margin-bottom: 10px; }
    table.data { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
    table.data th { background-color: #fafafa; text-align: left; padding: 6px 8px; border-bottom: 2px solid #e4e4e7; font-size: 10px; text-transform: uppercase; color: #71717a; }
    table.data td { padding: 6px 8px; border-bottom: 1px solid #f0f0f1; font-size: 11px; }
    .badge { padding: 2px 8px; border-radius: 10px; font-size: 9px; font-weight: bold; }
    .badge-pending { background-color: #fef3c7; color: #92400e; }
    .badge-approved { background-color: #dcfce7; color: #166534; }
    .badge-rejected { background-color: #fee2e2; color: #991b1b; }
    .badge-cancelled { background-color: #f4f4f5; color: #52525b; }
    .footer { margin-top: 30px; font-size: 9px; color: #a1a1aa; border-top: 1px solid #f0f0f1; padding-top: 8px; }
</style>
</head>
<body>

<div class="header">
    <p class="brand">CVLeave</p>
    <p class="subtitle">{{ __('Αναφορά Αδειών Υπαλλήλου') }} — {{ $year }}</p>
</div>

<table class="meta">
    <tr>
        <td class="label">{{ __('Όνομα') }}</td>
        <td class="value">{{ $user->name }}</td>
    </tr>
    <tr>
        <td class="label">{{ __('Email') }}</td>
        <td class="value">{{ $user->email }}</td>
    </tr>
    <tr>
        <td class="label">{{ __('Ημερομηνία πρόσληψης') }}</td>
        <td class="value">{{ $user->hire_date?->format('d/m/Y') ?? '—' }}</td>
    </tr>
    <tr>
        <td class="label">{{ __('Έτη προϋπηρεσίας εδώ') }}</td>
        <td class="value">{{ $user->yearsOfService() }}</td>
    </tr>
</table>

<h2>{{ __('Υπόλοιπο Ημερών') }} ({{ $year }})</h2>
<table class="data">
    <thead>
        <tr>
            <th>{{ __('Τύπος Άδειας') }}</th>
            <th>{{ __('Δικαιούται') }}</th>
            <th>{{ __('Χρησιμοποιήθηκαν') }}</th>
            <th>{{ __('Υπόλοιπο') }}</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($summary as $row)
        <tr>
            <td>{{ $row->leaveType->name }}</td>
            <td>{{ $row->entitled }}</td>
            <td>{{ $row->used }}</td>
            <td>{{ $row->remaining }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

<h2>{{ __('Ιστορικό Αιτήσεων') }}</h2>
<table class="data">
    <thead>
        <tr>
            <th>{{ __('Τύπος') }}</th>
            <th>{{ __('Από') }}</th>
            <th>{{ __('Έως') }}</th>
            <th>{{ __('Μέρες') }}</th>
            <th>{{ __('Κατάσταση') }}</th>
            <th>{{ __('Σημείωση / Αιτία') }}</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($history as $leaveRequest)
        <tr>
            <td>{{ $leaveRequest->leaveType->name }}</td>
            <td>{{ $leaveRequest->start_date->format('d/m/Y') }}</td>
            <td>{{ $leaveRequest->end_date->format('d/m/Y') }}</td>
            <td>{{ $leaveRequest->days_count }}</td>
            <td>
                <span class="badge badge-{{ $leaveRequest->status }}">
                    @switch($leaveRequest->status)
                        @case('pending') {{ __('Εκκρεμεί') }} @break
                        @case('approved') {{ __('Εγκρίθηκε') }} @break
                        @case('rejected') {{ __('Απορρίφθηκε') }} @break
                        @case('cancelled') {{ __('Ακυρώθηκε') }} @break
                    @endswitch
                </span>
            </td>
            <td>{{ $leaveRequest->rejection_reason ?? $leaveRequest->note ?? '—' }}</td>
        </tr>
        @empty
        <tr>
            <td colspan="6">{{ __('Δεν υπάρχουν αιτήσεις άδειας.') }}</td>
        </tr>
        @endforelse
    </tbody>
</table>

<div class="footer">
    {{ __('Δημιουργήθηκε από το CVLeave στις :date', ['date' => $generatedAt->format('d/m/Y H:i')]) }}
</div>

</body>
</html>
