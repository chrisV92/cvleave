{{--
    The files already on a task, shown inside the board's slide-over.

    Read-only on purpose: adding happens through the upload field below it, and
    removing stays on the full task page, where the attachment list is a proper
    table with its own confirmations.
--}}
@php($attachments = $task?->attachments ?? collect())

@if ($attachments->isEmpty())
    <p style="font-size: 13px; color: rgb(113 113 122); margin: 0;">
        {{ __('Δεν υπάρχουν συνημμένα ακόμα.') }}
    </p>
@else
    <div style="display: flex; flex-wrap: wrap; gap: 10px;">
        @foreach ($attachments as $attachment)
            <a
                href="{{ route('task-attachments.show', $attachment) }}"
                target="_blank"
                rel="noopener"
                title="{{ $attachment->original_name }}"
                style="display: flex; align-items: center; gap: 8px; max-width: 100%;
                       border: 1px solid rgb(228 228 231); border-radius: 8px;
                       padding: 6px 10px 6px 6px; text-decoration: none; color: inherit;"
            >
                @if ($attachment->isImage())
                    <img
                        src="{{ route('task-attachments.show', $attachment) }}"
                        alt="{{ $attachment->original_name }}"
                        style="width: 40px; height: 40px; object-fit: cover; border-radius: 6px; flex: 0 0 auto;"
                    >
                @else
                    <span style="width: 40px; height: 40px; border-radius: 6px; flex: 0 0 auto;
                                 background: rgb(244 244 245); display: flex; align-items: center;
                                 justify-content: center; font-size: 11px; font-weight: 700;
                                 color: rgb(113 113 122); text-transform: uppercase;">
                        {{ pathinfo($attachment->original_name, PATHINFO_EXTENSION) ?: '?' }}
                    </span>
                @endif

                <span style="min-width: 0;">
                    <span style="display: block; font-size: 13px; font-weight: 600;
                                 overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
                                 max-width: 180px;">{{ $attachment->original_name }}</span>
                    <span style="font-size: 11px; color: rgb(113 113 122);">{{ $attachment->humanSize() }}</span>
                </span>
            </a>
        @endforeach
    </div>
@endif
