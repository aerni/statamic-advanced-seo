<?php

namespace Aerni\AdvancedSeo\Actions;

use Aerni\AdvancedSeo\Context\Context;
use Aerni\AdvancedSeo\Facades\Seo;
use Illuminate\Support\Collection;
use Statamic\Facades\Blink;
use Statamic\Facades\Site;

class GetSiteDefaults
{
    public static function handle(mixed $model): Collection
    {
        $site = Context::from($model)?->site ?? Site::current()->handle();

        return Blink::once("advanced-seo::site::{$site}", function () use ($site) {
            return Seo::find('site::defaults')->in($site)->toAugmentedCollection();
        });
    }
}
