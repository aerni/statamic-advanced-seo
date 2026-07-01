<?php

namespace Aerni\AdvancedSeo\Support;

use Illuminate\Support\Collection;
use Statamic\Fields\Blueprint;
use Statamic\Fields\Field;
use Statamic\Fields\Fields;
use Statamic\Fields\Fieldtype;
use Statamic\Modifiers\CoreModifiers;

class ContentExtractor
{
    public function __construct(
        protected Blueprint $blueprint,
        protected array $content,
    ) {}

    public function run(): Collection
    {
        return $this->blueprint->fields()->all()
            ->mapWithKeys(function (Field $field) {
                $handle = $field->handle();
                $text = $this->extractTextFromField($field, data_get($this->content, $handle));

                return [$handle => $text];
            })
            ->filter();
    }

    protected function extractTextFromField(Field $field, mixed $value): string
    {
        $fieldtype = $field->fieldtype();

        return match ($field->type()) {
            'group' => $this->extractTextFromGroup($fieldtype, $value),
            'grid' => $this->extractTextFromGrid($fieldtype, $value),
            'replicator' => $this->extractTextFromReplicator($fieldtype, $value),
            'bard' => $this->extractTextFromBard($fieldtype, $value),
            default => in_array('text', $fieldtype->categories()) && is_string($value) ? $value : '',
        };
    }

    protected function extractTextFromGroup(Fieldtype $fieldtype, mixed $value): string
    {
        return $this->extractTextFromFields($fieldtype->fields(), $value);
    }

    protected function extractTextFromGrid(Fieldtype $fieldtype, mixed $rows): string
    {
        $fields = $fieldtype->fields();

        return collect($rows)
            ->filter(fn ($row) => is_array($row))
            ->filter(fn (array $row) => $row['enabled'] ?? true)
            ->map(fn (array $row) => $this->extractTextFromFields($fields, $row))
            ->filter()
            ->implode(' ');
    }

    protected function extractTextFromReplicator(Fieldtype $fieldtype, mixed $sets): string
    {
        return collect($sets)
            ->filter(fn ($set) => is_array($set))
            ->filter(fn (array $set) => $set['enabled'] ?? true)
            ->filter(fn (array $set) => ! empty($set['type']))
            ->map(fn (array $set) => $this->extractTextFromFields($fieldtype->fields($set['type']), $set))
            ->filter()
            ->implode(' ');
    }

    protected function extractTextFromBard(Fieldtype $fieldtype, mixed $nodes): string
    {
        $setText = collect($nodes)
            ->filter(fn ($node) => is_array($node))
            ->filter(fn (array $node) => ($node['type'] ?? null) === 'set')
            ->map(fn (array $node) => $node['attrs'] ?? [])
            ->filter(fn (array $attrs) => $attrs['enabled'] ?? true)
            ->filter(fn (array $attrs) => ! empty($attrs['values']['type']))
            ->map(fn (array $attrs) => $this->extractTextFromFields($fieldtype->fields($attrs['values']['type']), $attrs['values']));

        return collect([(new CoreModifiers)->bardText($nodes)])
            ->merge($setText)
            ->filter()
            ->implode(' ');
    }

    protected function extractTextFromFields(Fields $fields, mixed $values): string
    {
        return $fields->all()
            ->map(fn (Field $field) => $this->extractTextFromField($field, data_get($values, $field->handle())))
            ->filter()
            ->implode(' ');
    }
}
