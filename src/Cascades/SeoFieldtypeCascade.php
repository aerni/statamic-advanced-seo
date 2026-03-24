<?php

namespace Aerni\AdvancedSeo\Cascades;

class SeoFieldtypeCascade extends BaseCascade
{
    protected function process(): self
    {
        return $this
            ->withSiteDefaults()
            ->when(
                $this->model->isContent(),
                fn (self $cascade) => $cascade->withContentDefaults()
            )
            ->removeSeoPrefix()
            ->sortKeys();
    }
}
