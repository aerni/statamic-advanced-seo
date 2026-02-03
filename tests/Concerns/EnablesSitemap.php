<?php

namespace Aerni\AdvancedSeo\Tests\Concerns;

use Statamic\Facades\AssetContainer;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

trait EnablesSitemap
{
    use PreventsSavingStacheItemsToDisk;

    protected function setUpEnablesSitemap(): void
    {
        // Create the default asset container used by the addon for social images and favicons.
        AssetContainer::make('assets')->disk('local')->saveQuietly();
    }
}
