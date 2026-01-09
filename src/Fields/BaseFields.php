<?php

namespace Aerni\AdvancedSeo\Fields;

use Aerni\AdvancedSeo\Actions\EvaluateFeature;
use Aerni\AdvancedSeo\Context\Context;
use Aerni\AdvancedSeo\Contracts\Fields;
use Aerni\AdvancedSeo\Support\Helpers;

abstract class BaseFields implements Fields
{
    protected Context $context;

    public static function make(): self
    {
        return new static;
    }

    public function context(Context $context): self
    {
        $this->context = $context;

        return $this;
    }

    public function get(): array
    {
        $sections = $this->sections();

        if (! isset($this->context)) {
            return $sections;
        }

        return collect($sections)
            ->map(fn (array $section) => [
                ...$section,
                'fields' => collect($section['fields'])
                    ->filter(function (array $field): bool {
                        if (! $feature = $field['field']['feature'] ?? null) {
                            return true;
                        }

                        return EvaluateFeature::handle($feature, $this->context);
                    })
                    ->all(),
            ])
            ->filter(fn (array $section): bool => ! empty($section['fields']))
            ->values()
            ->all();
    }

    public function items(): array
    {
        return collect($this->get())
            ->flatMap(fn ($section) => $section['fields'])
            ->mapWithKeys(fn ($field) => [$field['handle'] => $field['field']])
            ->toArray();
    }

    protected function trans(string $key, array $placeholders = []): ?string
    {
        if (! isset($this->context)) {
            return null;
        }

        $placeholders = array_merge(['type' => $this->typePlaceholder()], $placeholders);

        return __("advanced-seo::fields.$key", $placeholders);
    }

    protected function typePlaceholder(): string
    {
        if (! isset($this->context)) {
            return '';
        }

        return match ($this->context->type) {
            'collections' => Helpers::isAddonCpRoute()
                ? lcfirst(__('advanced-seo::messages.entries'))
                : lcfirst(__('advanced-seo::messages.entry')),
            'taxonomies' => Helpers::isAddonCpRoute()
                ? lcfirst(__('advanced-seo::messages.terms'))
                : lcfirst(__('advanced-seo::messages.term')),
            default => ''
        };
    }

    abstract protected function sections(): array;
}
