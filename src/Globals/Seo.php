<?php

namespace Aerni\AdvancedSeo\Globals;

use Aerni\AdvancedSeo\Contracts\Globals;
use Facades\Aerni\AdvancedSeo\Blueprints\SeoGlobalsBlueprint;
use Illuminate\Support\Collection;
use Statamic\Facades\Fieldset;

class Seo implements Globals
{
    protected $handle = 'seo';
    protected $title = 'SEO';

    public function handle(): string
    {
        return $this->handle;
    }

    public function title(): string
    {
        return $this->title;
    }

    public function blueprint(): array
    {
        return SeoGlobalsBlueprint::contents();
    }

    public function fieldset(string $handle): Collection
    {
        return Fieldset::setDirectory(__DIR__ . '/../../resources/fieldsets/')
            ->find($handle)
            ->fields()
            ->all()
            ->map(function ($field) {
                return $field->config();
            });
    }
}
