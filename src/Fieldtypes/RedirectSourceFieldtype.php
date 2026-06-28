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
            'sites' => Site::all()->mapWithKeys(fn ($site) => [$site->handle() => rtrim($site->absoluteUrl(), '/')])->all(),
            'defaultSite' => Site::default()->handle(),
        ];
    }
}
