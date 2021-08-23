<?php

namespace Aerni\AdvancedSeo\Repositories;

use Aerni\AdvancedSeo\Repositories\DefaultsRepository;

class SiteDefaultsRepository extends DefaultsRepository
{
    public string $contentType = 'site';
    public string $handle = 'general';
}
