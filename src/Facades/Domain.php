<?php

namespace Aerni\AdvancedSeo\Facades;

use Aerni\AdvancedSeo\Registries\DomainRegistry;
use Illuminate\Support\Facades\Facade;

class Domain extends Facade
{
    protected static function getFacadeAccessor()
    {
        return DomainRegistry::class;
    }
}
