<?php

namespace Aerni\AdvancedSeo\UpdateScripts;

use Aerni\AdvancedSeo\Facades\Seo;
use Aerni\AdvancedSeo\Features\MultiSite;
use Statamic\Facades\Site;
use Statamic\UpdateScripts\UpdateScript;

class SetHreflangXDefault extends UpdateScript
{
    public function shouldUpdate($newVersion, $oldVersion): bool
    {
        return $this->isUpdatingTo('3.4.0');
    }

    public function update(): void
    {
        if (! MultiSite::enabled()) {
            return;
        }

        $config = Seo::find('site::defaults')?->config();

        if (! $config || filled($config->get('hreflang_x_default'))) {
            return;
        }

        $config->set('hreflang_x_default', Site::default()->handle())->save();
    }
}
