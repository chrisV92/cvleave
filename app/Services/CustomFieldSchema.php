<?php

namespace App\Services;

use App\Models\CustomField;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Support\CustomFieldType;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Carbon;

/**
 * Turns stored field definitions into Filament components.
 *
 * Everything user-defined in the Task Manager passes through here — the task
 * form, the task table, and later the board card — so this is the one place
 * that has to know how a definition becomes an input or a column.
 */
class CustomFieldSchema
{
    /** Form state for custom fields is nested under this key. */
    public const STATE_KEY = 'custom_fields';

    /**
     * Inputs for every field that applies to the given project.
     *
     * @return array<Field>
     */
    public static function formComponents(Project|int|null $project): array
    {
        return CustomField::forProject($project)
            ->map(fn (CustomField $field) => static::input($field))
            ->all();
    }

    protected static function input(CustomField $field): mixed
    {
        $name = self::STATE_KEY.'.'.$field->id;

        $component = match ($field->type) {
            CustomFieldType::Textarea => Textarea::make($name)->rows(3)->columnSpanFull(),

            CustomFieldType::Number => TextInput::make($name)->numeric(),

            CustomFieldType::Money => TextInput::make($name)
                ->numeric()
                ->prefix($field->options['currency'] ?? '€'),

            CustomFieldType::Percent => TextInput::make($name)
                ->numeric()
                ->suffix('%')
                ->minValue(0)
                ->maxValue(100),

            CustomFieldType::Date => DatePicker::make($name)->native(false),

            CustomFieldType::Select => Select::make($name)
                ->options($field->selectOptions())
                ->searchable()
                ->native(false),

            CustomFieldType::MultiSelect => Select::make($name)
                ->options($field->selectOptions())
                ->multiple()
                ->searchable()
                ->native(false),

            CustomFieldType::Checkbox => Toggle::make($name),

            CustomFieldType::User => Select::make($name)
                ->options(fn () => User::query()
                    ->where('tenant_id', $field->tenant_id)
                    ->orderBy('name')
                    ->pluck('name', 'id'))
                ->searchable(),

            // Text, and anything added later that has no special handling.
            default => TextInput::make($name)->maxLength(255),
        };

        return $component
            ->label($field->name)
            ->helperText($field->help_text)
            ->required($field->is_required);
    }

    /**
     * Toggleable table columns for the company's fields.
     *
     * Only company-wide definitions: the task table spans every project, and a
     * column for a field that exists on one board would be empty on all the
     * others. Project-specific fields show on the task form and on that
     * project's board instead.
     *
     * Hidden by default — a company with a dozen fields would otherwise get an
     * unreadable table on first visit.
     *
     * @return array<TextColumn|IconColumn>
     */
    public static function tableColumns(int $tenantId): array
    {
        return CustomField::query()
            ->where('tenant_id', $tenantId)
            ->companyWide()
            ->active()
            ->ordered()
            ->get()
            ->map(fn (CustomField $field) => static::column($field))
            ->all();
    }

    protected static function column(CustomField $field): mixed
    {
        $name = 'custom_field_'.$field->id;

        if ($field->type === CustomFieldType::Checkbox) {
            return IconColumn::make($name)
                ->label($field->name)
                ->boolean()
                ->getStateUsing(fn (Task $record) => static::valueFor($record, $field))
                ->toggleable(isToggledHiddenByDefault: true);
        }

        return TextColumn::make($name)
            ->label($field->name)
            ->placeholder('—')
            ->getStateUsing(fn (Task $record) => static::displayValue($record, $field))
            ->toggleable(isToggledHiddenByDefault: true);
    }

    protected static function valueFor(Task $task, CustomField $field): mixed
    {
        return $task->customFieldValues
            ->firstWhere('custom_field_id', $field->id)
            ?->value();
    }

    protected static function displayValue(Task $task, CustomField $field): ?string
    {
        $value = static::valueFor($task, $field);

        if ($value === null || $value === [] || $value === '') {
            return null;
        }

        return match ($field->type) {
            CustomFieldType::MultiSelect => implode(', ', (array) $value),
            CustomFieldType::Percent => $value.'%',
            CustomFieldType::Money => ($field->options['currency'] ?? '€').number_format((float) $value, 2),
            CustomFieldType::User => User::find($value)?->name,
            CustomFieldType::Date => Carbon::parse($value)->format('d/m/Y'),
            default => (string) $value,
        };
    }
}
