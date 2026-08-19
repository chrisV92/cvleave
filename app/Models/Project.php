<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'name',
        'slug',
        'description',
        'color',
        'owner_id',
        'archived_at',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'archived_at' => 'datetime',
            'position' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        // A board with no columns cannot be used, so a new project is never
        // left in that state — it starts from the company's defaults and can
        // be adjusted afterwards.
        static::created(function (self $project) {
            $project->seedStatusesFromCompanyDefaults();
        });

        // Tasks reference their status with ON DELETE RESTRICT, so that a
        // column cannot be removed while work still sits in it. That guard
        // would also block deleting the whole project, since the database
        // gives no order to the two cascades — so the tasks go first,
        // explicitly.
        static::deleting(function (self $project) {
            $project->tasks()->delete();
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function statuses(): HasMany
    {
        return $this->hasMany(TaskStatus::class)->orderBy('position')->orderBy('id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    /** Fields defined for this board specifically, not the company's. */
    public function customFields(): HasMany
    {
        return $this->hasMany(CustomField::class)->orderBy('position')->orderBy('id');
    }

    public function isArchived(): bool
    {
        return $this->archived_at !== null;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('archived_at');
    }

    /**
     * The column a newly created task lands in — the one flagged as default,
     * or failing that simply the first.
     */
    public function defaultStatus(): ?TaskStatus
    {
        return $this->statuses()->where('is_default', true)->first()
            ?? $this->statuses()->first();
    }

    public function seedStatusesFromCompanyDefaults(): void
    {
        if ($this->statuses()->exists()) {
            return;
        }

        $templates = TaskStatus::query()
            ->where('tenant_id', $this->tenant_id)
            ->templates()
            ->ordered()
            ->get();

        // A company created before the Task Manager existed has no templates
        // yet; seed them first so the project isn't left without columns.
        if ($templates->isEmpty()) {
            $this->tenant?->seedDefaultTaskStatuses();

            $templates = TaskStatus::query()
                ->where('tenant_id', $this->tenant_id)
                ->templates()
                ->ordered()
                ->get();
        }

        foreach ($templates as $template) {
            $this->statuses()->create([
                'tenant_id' => $this->tenant_id,
                'name' => $template->name,
                'color' => $template->color,
                'position' => $template->position,
                'is_default' => $template->is_default,
                'is_completed' => $template->is_completed,
            ]);
        }
    }
}
