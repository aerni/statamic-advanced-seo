<?php

namespace Aerni\AdvancedSeo\Fieldtypes;

use Aerni\AdvancedSeo\Concerns\EvaluatesIndexability;
use Aerni\AdvancedSeo\Context\Context;
use Statamic\Fields\Fieldtype;

class AlertFieldtype extends Fieldtype
{
    use EvaluatesIndexability;

    protected $selectable = false;

    public function preload(): array
    {
        return match ($this->config('alert')) {
            'indexing_disabled' => $this->indexingDisabled(),
            default => [],
        };
    }

    protected function indexingDisabled(): array
    {
        $context = Context::from($this->field->parent());

        $type = match ($context?->type) {
            'collections' => __('advanced-seo::messages.entry'),
            'taxonomies' => __('advanced-seo::messages.term'),
            default => __('content'),
        };

        return [
            'show' => $context?->site && ! $this->isIndexableSite($context->site),
            'message' => __('advanced-seo::messages.alert_indexing_disabled', ['type' => lcfirst($type)]),
        ];
    }
}
