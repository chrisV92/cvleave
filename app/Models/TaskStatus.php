<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A column of a project's board.
 *
 * A status with no project is one of the company's defaults — the template a
 * new project copies from. Projects hold their own copies so that renaming a
 * column on one board doesn't rename it everywhere.
 */
class TaskStatus extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'project_id',
        'name',
        'color',
        'position',
        'is_default',
        'is_completed',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'is_completed' => 'boolean',
            'position' => 'integer',
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

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    /** The company's template statuses, not any particular project's. */
    public function scopeTemplates(Builder $query): Builder
    {
        return $query->whereNull('project_id');
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('position')->orderBy('id');
    }
}
