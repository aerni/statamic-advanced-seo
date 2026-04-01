<?php

namespace Aerni\AdvancedSeo\Facades;

use Aerni\AdvancedSeo\Contracts\SeoDefaultsRepository;
use Illuminate\Support\Facades\Facade;

class Seo extends Facade
{
    protected static function getFacadeAccessor()
    {
        return SeoDefaultsRepository::class;
    }
}
