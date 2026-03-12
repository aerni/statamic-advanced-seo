<?php

namespace Aerni\AdvancedSeo\Tokens;

use Aerni\AdvancedSeo\Context\Context;
use Aerni\AdvancedSeo\Facades\Token;
use Aerni\AdvancedSeo\Support\Helpers;
use Aerni\AdvancedSeo\View\SeoFieldtypeCascade;
use Statamic\Contracts\Entries\Entry;
use Statamic\Contracts\Taxonomies\Term;
use Statamic\Facades\Antlers;
use Statamic\Facades\Blink;
use Statamic\Fields\Field;
use Statamic\Fields\Value;
use Statamic\Modifiers\CoreModifiers;
use Statamic\Support\Str;

class TokenParser
{
    protected array $parsing = [];

    public function parse(?string $data, Field $field): ?string
    {
        if ($data === null) {
            return null;
        }

        $parent = $field->parent();

        if (! ($parent instanceof Entry || $parent instanceof Term)) {
            return $data;
        }

        $parent = Helpers::localizedContent($parent);

        $data = $this->stripCircularReferences($data, $field);

        if (! Str::contains($data, '{{')) {
            return $data;
        }

        $this->parsing[] = $field->handle();

        try {
            $variables = $this->cascade($parent)->data()
                ->merge($parent->toAugmentedArray())
                ->when($field->type() !== 'json_ld', fn ($data) => $data->map($this->toPlainText(...)))
                ->all();

            return Antlers::parse($data, $variables);
        } finally {
            array_pop($this->parsing);
        }
    }

    /**
     * Strip references to the current field and any fields already being parsed
     * up the call stack to prevent infinite recursion during Antlers augmentation.
     */
    protected function stripCircularReferences(string $data, Field $field): string
    {
        $handles = array_unique([...$this->parsing, $field->handle()]);
        $escaped = array_map(fn ($h) => preg_quote($h, '/'), $handles);

        return preg_replace('/\{\{\s*(?:'.implode('|', $escaped).')\s*\}\}/', '', $data);
    }

    /**
     * Convert markup-producing field values to plain text,
     * capped at a generous limit to prevent large content dumps in meta tags.
     */
    protected function toPlainText(mixed $value): mixed
    {
        if (! $value instanceof Value) {
            return $value;
        }

        $text = Token::normalize($value);

        if ($text === null) {
            return $value;
        }

        return (new CoreModifiers)->safeTruncate($text, [320, '…']);
    }

    protected function cascade(mixed $parent): SeoFieldtypeCascade
    {
        $context = Context::from($parent);

        return Blink::once(
            "advanced-seo::cascade::fieldtype::{$context->id()}",
            fn () => SeoFieldtypeCascade::from($context)
        );
    }
}
