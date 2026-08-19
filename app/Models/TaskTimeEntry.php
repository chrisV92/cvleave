<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One stretch of work on a task.
 *
 * A row with no ended_at is a timer still running.
 */
class TaskTimeEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_id',
        'user_id',
        'started_at',
        'ended_at',
        'duration_seconds',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'duration_seconds' => 'integer',
        ];
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isRunning(): bool
    {
        return $this->ended_at === null;
    }

    /** Seconds so far, counting a running timer up to now. */
    public function seconds(): int
    {
        if ($this->isRunning()) {
            return $this->started_at->diffInSeconds(now());
        }

        return $this->duration_seconds ?? 0;
    }

    public function scopeRunning(Builder $query): Builder
    {
        return $query->whereNull('ended_at');
    }

    /** "2ω 15λ", or "—" for nothing worth showing. */
    public static function humanise(?int $seconds): string
    {
        if (! $seconds) {
            return '—';
        }

        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);

        if ($hours && $minutes) {
            return __(':hoursω :minutesλ', ['hours' => $hours, 'minutes' => $minutes]);
        }

        if ($hours) {
            return __(':hoursω', ['hours' => $hours]);
        }

        return __(':minutesλ', ['minutes' => max($minutes, 1)]);
    }
}
