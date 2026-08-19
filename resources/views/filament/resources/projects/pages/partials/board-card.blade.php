{{--
    One card on the board.

    Its own file because the server re-renders a single card after an edit —
    the board is wire:ignore'd, so a save cannot refresh it the usual way, and
    duplicating this markup in JavaScript would guarantee the two drift apart.
--}}
@php
    $priorities = \App\Models\Task::priorities();
    $priorityColour = [
        'urgent' => ['#fee2e2', '#b91c1c'],
        'high' => ['#fef3c7', '#b45309'],
        'normal' => ['#dbeafe', '#1d4ed8'],
        'low' => ['#f4f4f5', '#52525b'],
    ];
@endphp

<div
    class="kb-card {{ $canMove ? 'kb-movable' : '' }}"
    data-task="{{ $task->id }}"
    role="button"
    tabindex="0"
    x-on:click="open({{ $task->id }})"
    x-on:keydown.enter.prevent="open({{ $task->id }})"
>
    <div class="kb-title">{{ $task->title }}</div>

    @foreach ($fields as $field)
        <div class="kb-field">{{ $field['label'] }}: <strong>{{ $field['value'] }}</strong></div>
    @endforeach

    <div class="kb-meta">
        @if ($task->priority)
            @php([$bg, $fg] = $priorityColour[$task->priority] ?? ['#f4f4f5', '#52525b'])
            <span class="kb-badge" style="background: {{ $bg }}; color: {{ $fg }}">
                {{ $priorities[$task->priority] ?? $task->priority }}
            </span>
        @endif

        @if ($task->due_date)
            <span class="kb-badge {{ $task->isOverdue() ? 'kb-due-late' : '' }}"
                  style="background: transparent; padding-left: 0">
                {{ $task->due_date->format('d/m') }}
            </span>
        @endif

        @if ($task->assignee)
            <span class="kb-who">{{ $task->assignee->name }}</span>
        @endif
    </div>
</div>
