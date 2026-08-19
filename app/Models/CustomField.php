<?php

namespace App\Models;

use App\Support\CustomFieldType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A field a company added to its tasks.
 *
 * With no project it applies to every board in the company; with one it
 * applies only to that board.
 */
class CustomField extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'project_id',
        'key',
        'name',
        'type',
        'options',
        'help_text',
        'is_required',
        'is_active',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'type' => CustomFieldType::class,
            'options' => 'array',
            'is_required' => 'boolean',
            'is_active' => 'boolean',
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

    public function values(): HasMany
    {
        return $this->hasMany(CustomFieldValue::class);
    }

    /** Company-wide definitions, which apply to every project. */
    public function scopeCompanyWide(Builder $query): Builder
    {
        return $query->whereNull('project_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('position')->orderBy('id');
    }

    /**
     * Everything that applies to a given project: the company's own fields
     * plus that project's, in display order.
     *
     * @return Collection<int, self>
     */
    public static function forProject(Project|int|null $project): Collection
    {
        if ($project === null) {
            return self::query()->whereRaw('1 = 0')->get();
        }

        $project = $project instanceof Project ? $project : Project::find($project);

        if (! $project) {
            return self::query()->whereRaw('1 = 0')->get();
        }

        return self::query()
            ->where('tenant_id', $project->tenant_id)
            ->where(fn (Builder $query) => $query
                ->whereNull('project_id')
                ->orWhere('project_id', $project->id))
            ->active()
            ->ordered()
            ->get();
    }

    /** @return array<string, string> */
    public function selectOptions(): array
    {
        $options = collect($this->options ?? [])
            ->map(fn ($option) => is_array($option) ? ($option['value'] ?? null) : $option)
            ->filter()
            ->values();

        return $options->combine($options)->all();
    }
}
