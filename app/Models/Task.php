<?php

namespace App\Models;

use App\Models\Concerns\HasCustomFields;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Task extends Model
{
    use HasCustomFields;
    use HasFactory;

    public const PRIORITY_LOW = 'low';

    public const PRIORITY_NORMAL = 'normal';

    public const PRIORITY_HIGH = 'high';

    public const PRIORITY_URGENT = 'urgent';

    protected $fillable = [
        'tenant_id',
        'project_id',
        'task_status_id',
        'title',
        'description',
        'assignee_id',
        'created_by',
        'priority',
        'start_date',
        'due_date',
        'position',
        'completed_at',
        'archived_at',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'due_date' => 'date',
            'completed_at' => 'datetime',
            'archived_at' => 'datetime',
            'position' => 'float',
        ];
    }

    protected static function booted(): void
    {
        // `completed_at` follows the status rather than being set by hand, so
        // that "when was this finished" stays true however the task was moved
        // — through the board, the table, or an import.
        static::saving(function (self $task) {
            if (! $task->isDirty('task_status_id')) {
                return;
            }

            // Deliberately not $task->status: the relation still holds the
            // status the task was moved *out of*, so asking it would decide
            // completion from the previous column.
            $isCompleted = (bool) TaskStatus::find($task->task_status_id)?->is_completed;

            if ($isCompleted && $task->completed_at === null) {
                $task->completed_at = now();
            }

            if (! $isCompleted) {
                $task->completed_at = null;
            }
        });
    }

    public static function priorities(): array
    {
        return [
            self::PRIORITY_LOW => __('Χαμηλή'),
            self::PRIORITY_NORMAL => __('Κανονική'),
            self::PRIORITY_HIGH => __('Υψηλή'),
            self::PRIORITY_URGENT => __('Επείγουσα'),
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(TaskStatus::class, 'task_status_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function timeEntries(): HasMany
    {
        return $this->hasMany(TaskTimeEntry::class)->latest('started_at');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(TaskAttachment::class)->latest();
    }

    public function comments(): HasMany
    {
        return $this->hasMany(TaskComment::class)->oldest();
    }

    /** Total time logged, including any timer still running. */
    public function trackedSeconds(): int
    {
        return (int) $this->timeEntries->sum(fn (TaskTimeEntry $entry) => $entry->seconds());
    }

    public function isOverdue(): bool
    {
        return $this->due_date !== null
            && $this->completed_at === null
            && $this->due_date->isPast();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('archived_at');
    }
}
