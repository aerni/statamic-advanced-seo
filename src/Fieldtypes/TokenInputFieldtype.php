<?php

namespace Aerni\AdvancedSeo\Fieldtypes;

use Aerni\AdvancedSeo\Context\Context;
use Aerni\AdvancedSeo\Data\SeoSetLocalization;
use Illuminate\Support\Collection as SupportCollection;
use Statamic\Contracts\Entries\Collection;
use Statamic\Contracts\Taxonomies\Taxonomy;
use Statamic\Facades\Blink;
use Statamic\Fields\Blueprint;
use Statamic\Fields\Field;
use Statamic\Fields\Fieldtype;

class TokenInputFieldtype extends Fieldtype
{
    protected static $handle = 'token_input';

    protected $categories = ['text'];

    protected $selectable = false;

    protected const ALLOWED_FIELD_TYPES = [
        'token_input', 'text', 'textarea', 'slug'
    ];

    protected const ALLOWED_SEO_FIELDS = [
        'seo_title', 'seo_description', 'seo_og_title', 'seo_og_description',
    ];

    public function preload(): array
    {
        return [
            'fields' => $this->resolveFields(),
        ];
    }

    protected function resolveFields(): SupportCollection
    {
        $parent = $this->field->parent();

        $fields = Blink::once("advanced-seo.antlers-input-fields.{$parent->id()}", function () use ($parent) {
            $blueprints = $parent instanceof SeoSetLocalization
                ? $this->defaultsBlueprints($parent)
                : collect([$this->contentBlueprint($parent)]);

            return $blueprints
                ->map($this->allowedFieldTypeFields(...))
                ->reduce(fn ($carry, $fields) => $carry ? $carry->intersectByKeys($fields) : $fields)
                ->merge($this->allowedSeoFields($blueprints))
                ->map(fn (Field $field) => [
                    'handle' => $field->handle(),
                    'display' => $field->display(),
                    'type' => $field->type(),
                ])
                ->sortBy('display');
        });

        return $fields
            ->reject(fn (array $field) => $field['handle'] === $this->field->handle())
            ->values();
    }

    protected function defaultsBlueprints(SeoSetLocalization $parent): SupportCollection
    {
        $seoSetParent = Context::from($parent)->seoSet()->parent();

        return match (true) {
            $seoSetParent instanceof Collection => $seoSetParent->entryBlueprints(),
            $seoSetParent instanceof Taxonomy => $seoSetParent->termBlueprints(),
        };
    }

    protected function contentBlueprint(mixed $parent): Blueprint
    {
        return match (true) {
            $parent instanceof Collection => $parent->entryBlueprint(request('blueprint')),
            $parent instanceof Taxonomy => $parent->termBlueprint(request('blueprint')),
            default => $parent->blueprint(),
        };
    }

    protected function allowedFieldTypeFields(Blueprint $blueprint): SupportCollection
    {
        return $blueprint->fields()->all()
            ->filter(fn (Field $field) => in_array($field->type(), self::ALLOWED_FIELD_TYPES));
    }

    protected function allowedSeoFields(SupportCollection $blueprints): SupportCollection
    {
        $parent = $this->field->parent();

        $blueprint = $parent instanceof SeoSetLocalization
            ? $parent->blueprint()
            : $blueprints->first();

        return $blueprint->fields()->all()->only(self::ALLOWED_SEO_FIELDS);
    }
}
