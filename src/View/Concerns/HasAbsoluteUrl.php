<?php

namespace Aerni\AdvancedSeo\View\Concerns;

use Statamic\Facades\URL;

trait HasAbsoluteUrl
{
    protected function absoluteUrl(object $model): ?string
    {
        if (method_exists($this, 'baseUrl') && $this->baseUrl()) {
            return URL::assemble($this->baseUrl(), $model->url());
        }

        return $model->absoluteUrl();
    }
}
