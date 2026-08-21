<?php

namespace Aerni\AdvancedSeo\Fieldtypes;

use Statamic\Facades\Site;
use Statamic\Fields\Fieldtype;

class RedirectSourceFieldtype extends Fieldtype
{
    protected $selectable = false;

    public function preload(): array
    {
        return [
            'sites' => Site::authorized()->mapWithKeys(fn ($site) => [$site->handle() => $site->name()])->all(),
            'defaultSite' => Site::default()->handle(),
            'multisite' => Site::authorized()->count() > 1,
        ];
    }
}
