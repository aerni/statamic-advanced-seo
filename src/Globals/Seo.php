<?php

namespace Aerni\AdvancedSeo\Globals;

use Aerni\AdvancedSeo\Contracts\Globals;
use Facades\Aerni\AdvancedSeo\Blueprints\SeoGlobalsBlueprint;

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
}
