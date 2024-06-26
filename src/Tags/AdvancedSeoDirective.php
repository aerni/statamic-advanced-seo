<?php

namespace Aerni\AdvancedSeo\Tags;

class AdvancedSeoDirective
{
    public function __construct(protected AdvancedSeoTags $tags)
    {
        //
    }

    public function render(string $tag, array $context): mixed
    {
        return $this->tags->setContext($context)->$tag();
    }
}
