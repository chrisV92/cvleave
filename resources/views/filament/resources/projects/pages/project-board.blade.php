<x-filament-panels::page>
<style>
    .kb-board { display: flex; gap: 16px; align-items: flex-start; overflow-x: auto; padding-bottom: 12px; }
    .kb-col { flex: 0 0 300px; background: rgb(244 244 245); border-radius: 12px; padding: 10px; }
    .dark .kb-col { background: rgb(39 39 42); }
    .kb-col-head { display: flex; align-items: center; gap: 8px; padding: 4px 6px 10px 6px; }
    .kb-dot { width: 10px; height: 10px; border-radius: 999px; flex: 0 0 auto; }
    .kb-col-name { font-weight: 600; font-size: 14px; }
    .kb-count { margin-left: auto; font-size: 12px; color: rgb(113 113 122); font-variant-numeric: tabular-nums; }
    .kb-list { display: flex; flex-direction: column; gap: 8px; min-height: 60px; }
    .kb-card {
        background: #fff; border: 1px solid rgb(228 228 231); border-radius: 10px;
        padding: 10px 12px; box-shadow: 0 1px 2px rgba(0,0,0,.04);
        display: block; text-decoration: none; color: inherit;
    }
    .dark .kb-card { background: rgb(24 24 27); border-color: rgb(63 63 70); }
    .kb-card:hover { border-color: rgb(165 180 252); }
    .kb-movable { cursor: grab; }
    .kb-movable:active { cursor: grabbing; }
    .kb-title { font-size: 14px; font-weight: 600; line-height: 1.4; }
    .kb-meta { display: flex; flex-wrap: wrap; gap: 6px; align-items: center; margin-top: 8px; }
    .kb-badge { font-size: 11px; padding: 2px 7px; border-radius: 999px; font-weight: 600; }
    .kb-who { font-size: 12px; color: rgb(82 82 91); margin-left: auto; }
    .dark .kb-who { color: rgb(161 161 170); }
    .kb-field { font-size: 11px; color: rgb(113 113 122); margin-top: 6px; }
    .kb-due-late { color: rgb(220 38 38); font-weight: 600; }
    .kb-ghost { opacity: .4; }
    .kb-drag { transform: rotate(1.5deg); }
    .kb-add {
        margin-left: 6px; width: 22px; height: 22px; line-height: 1; flex: 0 0 auto;
        border-radius: 6px; border: 0; cursor: pointer; font-size: 16px; font-weight: 600;
        color: rgb(113 113 122); background: transparent;
    }
    .kb-add:hover { background: rgb(228 228 231); color: rgb(63 63 70); }
    .dark .kb-add:hover { background: rgb(63 63 70); color: rgb(228 228 231); }
    .kb-empty { font-size: 12px; color: rgb(161 161 170); text-align: center; padding: 14px 0; }
</style>

@php
    $canMove = $this->canMove();
@endphp

{{-- Careful: this whole expression lives inside a double-quoted HTML
     attribute, so a single " anywhere in it — comments included — silently
     truncates the attribute and the board stops initialising. --}}
<div
    x-data="{
        init() {
            if (! @js($canMove)) return;

            // Loaded on demand rather than from a <script> tag, so the board
            // works whether or not the panel is in SPA mode — with SPA on,
            // a tag in the body would not re-run on navigation.
            if (window.Sortable) {
                this.attach();
            } else {
                const script = document.createElement('script');
                script.src = @js(asset('js/sortable.min.js'));
                script.onload = () => this.attach();
                document.head.appendChild(script);
            }
        },

        attach() {
            this.$el.querySelectorAll('[data-column]').forEach((list) => {
                Sortable.create(list, {
                    group: 'board',
                    animation: 150,
                    ghostClass: 'kb-ghost',
                    dragClass: 'kb-drag',
                    // Without this the empty-state placeholder is a sortable
                    // item too, and dropping next to it sends a neighbour id
                    // of NaN to the server.
                    draggable: '[data-task]',
                    onStart: () => { this.dragging = true; },
                    onEnd: (event) => {
                        // SortableJS fires a click on the card it just dropped.
                        // Without this, every drag would also open the panel.
                        setTimeout(() => { this.dragging = false; }, 80);

                        const card = event.item;
                        const list = event.to;

                        // Neighbours come from the DOM after the drop, so the
                        // server is told where the card actually landed rather
                        // than where the browser guessed it would.
                        const previous = this.neighbour(card, 'previousElementSibling');
                        const next = this.neighbour(card, 'nextElementSibling');

                        this.refresh(event.from);
                        this.refresh(event.to);

                        $wire.moveTask(
                            Number(card.dataset.task),
                            Number(list.dataset.column),
                            previous,
                            next,
                        );
                    },
                });
            });
        },

        /** Whether a drag is in flight, so its trailing click is ignored. */
        dragging: false,

        /** Open the panel to add a task, optionally into a given column. */
        add(columnId) {
            $wire.mountAction('createTask', { column: columnId });
        },

        /** Put a newly created card at the end of its column. */
        appendCard({ columnId, html }) {
            const list = this.$el.querySelector(`[data-column='${columnId}']`);
            if (! list) return;

            const holder = document.createElement('div');
            holder.innerHTML = html.trim();
            const card = holder.firstElementChild;

            // Before the empty-state placeholder, which always sits last.
            const empty = list.querySelector('.kb-empty');
            empty ? list.insertBefore(card, empty) : list.appendChild(card);

            this.refresh(list);
        },

        /** Open the edit panel for a card. */
        open(taskId) {
            if (this.dragging) return;

            $wire.mountAction('editTask', { task: taskId });
        },

        /**
         * Swap in the card the server just re-rendered.
         *
         * The board is wire:ignore'd so a save never repaints it; this patches
         * the one card that changed, and moves it if its column did.
         */
        applyUpdate({ id, columnId, html }) {
            const card = this.$el.querySelector(`[data-task='${id}']`);
            if (! card) return;

            const from = card.closest('[data-column]');
            const target = this.$el.querySelector(`[data-column='${columnId}']`);

            const holder = document.createElement('div');
            holder.innerHTML = html.trim();
            const fresh = holder.firstElementChild;

            card.replaceWith(fresh);

            if (target && from !== target) {
                target.appendChild(fresh);
                this.refresh(from);
                this.refresh(target);
            }
        },

        /** The id of the nearest sibling that is actually a card. */
        neighbour(card, direction) {
            let sibling = card[direction];

            while (sibling && ! sibling.dataset.task) {
                sibling = sibling[direction];
            }

            return sibling ? Number(sibling.dataset.task) : null;
        },

        /**
         * The card count and the empty-state are rendered server-side, and the
         * board is wire:ignore'd so a move never re-renders it. Both are
         * corrected here instead of paying for a round trip.
         */
        refresh(list) {
            const count = list.querySelectorAll('[data-task]').length;
            const column = list.closest('.kb-col');

            column.querySelector('[data-count]').textContent = count;

            const empty = list.querySelector('.kb-empty');
            if (empty) {
                empty.style.display = count ? 'none' : '';
            }
        },
    }"
    x-on:board-card-updated.window="applyUpdate($event.detail)"
    x-on:board-card-added.window="appendCard($event.detail)"
    class="kb-board"
    wire:ignore
>
    @foreach ($this->getColumns() as $column)
        <div class="kb-col">
            <div class="kb-col-head">
                <span class="kb-dot" style="background: {{ $column->color }}"></span>
                <span class="kb-col-name">{{ $column->name }}</span>
                <span class="kb-count" data-count>{{ $column->tasks_count }}</span>

                @if ($canMove)
                    <button
                        type="button"
                        class="kb-add"
                        title="{{ __('Νέα εργασία σε αυτή τη στήλη') }}"
                        x-on:click="add({{ $column->id }})"
                    >+</button>
                @endif
            </div>

            <div class="kb-list" data-column="{{ $column->id }}">
                @foreach ($this->tasksFor($column) as $task)
                    @include('filament.resources.projects.pages.partials.board-card', [
                        'task' => $task,
                        'fields' => $this->cardFields($task),
                        'canMove' => $canMove,
                    ])
                @endforeach

                <div class="kb-empty" @style(['display: none' => $column->tasks_count > 0])>
                    {{ __('Καμία εργασία') }}
                </div>
            </div>
        </div>
    @endforeach
</div>

{{-- Renders the slide-over the cards mount. --}}
<x-filament-actions::modals />
</x-filament-panels::page>
