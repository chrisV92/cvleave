<?php

namespace App\Support;

/**
 * The kinds of custom field a company can define, and where each one's value
 * is physically stored.
 *
 * Values live in typed columns rather than a single JSON blob. MariaDB 10.11
 * has no native JSON type and no multi-valued indexes, so a JSON column would
 * mean a full table scan every time somebody filters or sorts by a custom
 * field — which is most of what people do with them.
 */
enum CustomFieldType: string
{
    case Text = 'text';
    case Textarea = 'textarea';
    case Number = 'number';
    case Money = 'money';
    case Percent = 'percent';
    case Date = 'date';
    case Select = 'select';
    case MultiSelect = 'multiselect';
    case Checkbox = 'checkbox';
    case User = 'user';

    public function label(): string
    {
        return match ($this) {
            self::Text => __('Κείμενο'),
            self::Textarea => __('Μεγάλο κείμενο'),
            self::Number => __('Αριθμός'),
            self::Money => __('Ποσό'),
            self::Percent => __('Ποσοστό'),
            self::Date => __('Ημερομηνία'),
            self::Select => __('Λίστα επιλογών'),
            self::MultiSelect => __('Πολλαπλή επιλογή'),
            self::Checkbox => __('Ναι / Όχι'),
            self::User => __('Χρήστης'),
        };
    }

    /** The column on custom_field_values that holds this type's value. */
    public function valueColumn(): string
    {
        return match ($this) {
            self::Text, self::Select => 'value_string',
            self::Textarea => 'value_text',
            self::Number, self::Money, self::Percent, self::User => 'value_number',
            self::Date => 'value_date',
            self::Checkbox => 'value_boolean',
            self::MultiSelect => 'value_json',
        };
    }

    /** Whether the field definition needs a list of choices. */
    public function hasOptions(): bool
    {
        return in_array($this, [self::Select, self::MultiSelect], true);
    }

    /** @return array<string, string> */
    public static function labels(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $type) => [$type->value => $type->label()])
            ->all();
    }

    /**
     * Cast a value coming out of the database back to something the form and
     * table can use.
     */
    public function cast(mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        return match ($this) {
            self::Number, self::Money, self::Percent => (float) $value,
            self::User => (int) $value,
            self::Checkbox => (bool) $value,
            self::MultiSelect => is_array($value) ? $value : (json_decode($value, true) ?? []),
            default => $value,
        };
    }
}
