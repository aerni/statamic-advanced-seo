<?php

namespace Aerni\AdvancedSeo\Support;

use Aerni\AdvancedSeo\Context\Context;
use Aerni\AdvancedSeo\View\SeoFieldtypeCascade;
use Illuminate\Support\Str;
use Statamic\Contracts\Entries\Entry;
use Statamic\Contracts\Taxonomies\Term;
use Statamic\Facades\Antlers;
use Statamic\Facades\Blink;
use Statamic\Fields\Field;
use Statamic\Fields\Value;
use Statamic\Modifiers\CoreModifiers;

class AntlersParser
{
    protected static array $parsing = [];

    public static function parse(?string $data, Field $field): ?string
    {
        if ($data === null) {
            return null;
        }

        $parent = $field->parent();

        if (! ($parent instanceof Entry || $parent instanceof Term)) {
            return $data;
        }

        if ($parent instanceof Term) {
            $parent = $parent->in(Context::from($parent)->site);
        }

        $data = static::stripCircularReferences($data, $field);

        if (! Str::contains($data, '{{')) {
            return $data;
        }

        static::$parsing[] = $field->handle();

        try {
            $variables = static::cascade($field->parent())->data()
                ->merge($parent->toAugmentedArray())
                ->map(static::toPlainText(...))
                ->all();

            return Antlers::parse($data, $variables);
        } finally {
            array_pop(static::$parsing);
        }
    }

    /**
     * Strip references to the current field and any fields already being parsed
     * up the call stack to prevent infinite recursion during Antlers augmentation.
     */
    protected static function stripCircularReferences(string $data, Field $field): string
    {
        $handles = array_unique([...static::$parsing, $field->handle()]);
        $escaped = array_map(fn ($h) => preg_quote($h, '/'), $handles);

        return preg_replace('/\{\{\s*(?:'.implode('|', $escaped).')\s*\}\}/', '', $data);
    }

    /**
     * Convert markup-producing field values to plain text,
     * capped at a generous limit to prevent large content dumps in meta tags.
     */
    protected static function toPlainText(mixed $value): mixed
    {
        if (! $value instanceof Value) {
            return $value;
        }

        $text = match ($value->fieldtype()?->handle()) {
            'markdown' => (new CoreModifiers)->stripTags($value->value(), [], []),
            'bard' => (new CoreModifiers)->bardText($value),
            default => null,
        };

        if ($text === null) {
            return $value;
        }

        return (new CoreModifiers)->safeTruncate($text, [320, '…']);
    }

    protected static function cascade(mixed $parent): SeoFieldtypeCascade
    {
        $context = Context::from($parent);

        return Blink::once(
            "advanced-seo::cascade::fieldtype::{$context->id()}",
            fn () => SeoFieldtypeCascade::from($context)
        );
    }
}
