<?php

namespace Aerni\AdvancedSeo\Fieldtypes;

use Aerni\AdvancedSeo\Actions\GetAuthorizedSites;
use Statamic\Fields\Fieldtype;

class HreflangXDefaultFieldtype extends Fieldtype
{
    protected $selectable = false;

    public function preload(): array
    {
        $set = $this->field->parent();
        $authorizedSites = GetAuthorizedSites::handle($set)->map->handle();

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
