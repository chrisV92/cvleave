<?php

namespace App\Models\Concerns;

use App\Models\CustomField;
use App\Models\CustomFieldValue;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Reading and writing the values of user-defined fields on a task.
 *
 * Values are addressed by the field's numeric id rather than its key. Keys are
 * only unique within a company and a project, so two different fields could
 * share one — the id is the thing that actually identifies a definition.
 */
trait HasCustomFields
{
    public function customFieldValues(): HasMany
    {
        return $this->hasMany(CustomFieldValue::class);
    }

    /**
     * Current values keyed by field id, ready to hydrate a form.
     *
     * @return array<int, mixed>
     */
    public function customFieldState(): array
    {
        return $this->customFieldValues()
            ->with('customField')
            ->get()
            ->filter(fn (CustomFieldValue $value) => $value->customField !== null)
            ->mapWithKeys(fn (CustomFieldValue $value) => [$value->custom_field_id => $value->value()])
            ->all();
    }

    /**
     * Write the given values, addressed by field id.
     *
     * Only fields that actually apply to this task's project are written, so a
     * crafted form submission cannot attach another project's field — or
     * another company's — to this task.
     *
     * @param  array<int|string, mixed>  $state
     */
    public function saveCustomFieldState(array $state): void
    {
        $applicable = CustomField::forProject($this->project)->keyBy('id');

        foreach ($state as $fieldId => $value) {
            $field = $applicable->get((int) $fieldId);

            if (! $field) {
                continue;
            }

            $column = $field->type->valueColumn();

            // Blank means "no answer", and an empty row would otherwise be
            // indistinguishable from one holding an empty string.
            if ($value === null || $value === '' || $value === []) {
                $this->customFieldValues()->where('custom_field_id', $field->id)->delete();

                continue;
            }

            $this->customFieldValues()->updateOrCreate(
                ['custom_field_id' => $field->id],
                [
                    'value_string' => null,
                    'value_text' => null,
                    'value_number' => null,
                    'value_date' => null,
                    'value_boolean' => null,
                    'value_json' => null,
                    $column => $value,
                ],
            );
        }
    }
}
