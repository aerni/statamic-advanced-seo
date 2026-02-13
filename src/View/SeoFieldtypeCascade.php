<?php

namespace Aerni\AdvancedSeo\View;

use Aerni\AdvancedSeo\Context\Context;

class SeoFieldtypeCascade extends BaseCascade
{
    public function __construct(Context $model)
    {
        parent::__construct($model);
    }

    protected function process(): self
    {
        return $this
            ->withSiteDefaults()
            ->withContentDefaults()
            ->removeSeoPrefix()
            ->ensureOverrides()
            ->sortKeys();
    }
}
