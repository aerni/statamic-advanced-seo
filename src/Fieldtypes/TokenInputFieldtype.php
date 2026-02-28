<?php

namespace Aerni\AdvancedSeo\Fieldtypes;

use Aerni\AdvancedSeo\Context\Context;
use Aerni\AdvancedSeo\Data\SeoSetLocalization;
use Aerni\AdvancedSeo\Facades\Seo;
use Aerni\AdvancedSeo\Support\AntlersParser;
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
        'token_input', 'text', 'textarea', 'markdown', 'bard',
    ];

    protected const ALLOWED_SEO_FIELDS = [
        'seo_title', 'seo_description', 'seo_og_title', 'seo_og_description',
    ];

    protected const SITE_TOKEN_FIELDS = [
        'separator', 'site_name',
    ];

    public function augment($value)
    {
        return AntlersParser::parse($value, $this->field);
    }

    public function preload(): array
    {
        return [
            'tokens' => $this->resolveFieldTokens()->merge($this->resolveSiteTokens())->values(),
        ];
    }

    protected function resolveFieldTokens(): SupportCollection
    {
        $parent = $this->field->parent();

        $fields = Blink::once("advanced-seo.token-input-fields.{$parent->id()}", function () use ($parent) {
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
                    'group' => 'fields',
                ])
                ->sortBy('display');
        });

        return $fields
            ->reject(fn (array $field) => $field['handle'] === $this->field->handle())
            ->values();
    }

    protected function resolveSiteTokens(): SupportCollection
    {
        $parent = $this->field->parent();

        return Blink::once("advanced-seo.token-input-site-tokens.{$parent->id()}", function () use ($parent) {
            $context = Context::from($parent);
            $defaults = Seo::find('site::defaults')->in($context->site);

            return $defaults->blueprint()->fields()->all()
                ->only(self::SITE_TOKEN_FIELDS)
                ->map(fn (Field $field) => [
                    'handle' => $field->handle(),
                    'display' => $field->display(),
                    'type' => 'token',
                    'group' => 'common',
                    'value' => $defaults->value($field->handle()),
                ]);
        });
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
