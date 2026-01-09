<?php

namespace Aerni\AdvancedSeo\Concerns;

use Aerni\AdvancedSeo\Context\Context;
use Aerni\AdvancedSeo\Enums\Scope;
use Statamic\Support\Traits\FluentlyGetsAndSets;

trait HasContext
{
    use FluentlyGetsAndSets;

    protected $context;

    abstract protected function scope(): Scope;

    public function context(?Context $context = null): Context|self
    {
        return $this->fluentlyGetOrSet('context')
            ->getter(function ($context) {
                return $context ?? new Context(
                    type: $this->type(),
                    handle: $this->handle(),
                    scope: $this->scope(),
                    site: method_exists($this, 'locale') ? $this->locale() : null,
                );
            })->args(func_get_args());
    }
}
