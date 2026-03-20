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
            ->when(
                $this->model->isContent(),
                fn (self $cascade) => $cascade->withContentDefaults()
            )
            ->removeSeoPrefix()
            ->ensureOverrides()
            ->sortKeys();
    }
}
