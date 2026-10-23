<?php

namespace Aerni\AdvancedSeo\Fieldtypes;

use Statamic\Facades\Site;
use Statamic\Fields\Fieldtype;

class HreflangXDefaultFieldtype extends Fieldtype
{
    protected $selectable = false;

    public function preload(): array
    {
        $set = $this->field->parent();
        $authorizedSites = Site::authorized()->map->handle();

        return [
            'options' => $set->sites()
                ->map(fn ($site) => [
                    'value' => $site->handle(),
                    'label' => $site->name(),
                    'disabled' => ! $authorizedSites->contains($site->handle()),
                ])
                ->values()
                ->all(),
        ];
    }
}
