<?php

namespace Aerni\AdvancedSeo\View;

use Aerni\AdvancedSeo\Data\DefaultsData;

class SourceFieldtypeCascade extends BaseCascade
{
    public function __construct(DefaultsData $model)
    {
        parent::__construct($model);
    }

    protected function process(): self
    {
        return $this
            ->withSiteDefaults()
            ->withContentDefaults()
            ->removeSeoPrefix()
            ->removeSectionFields()
            ->ensureOverrides()
            ->sortKeys();
    }
}
