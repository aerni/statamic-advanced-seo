<?php

namespace Aerni\AdvancedSeo\Globals;

use Aerni\AdvancedSeo\Contracts\Globals;

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
}
