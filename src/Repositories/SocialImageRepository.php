<?php

namespace Aerni\AdvancedSeo\Repositories;

use Aerni\AdvancedSeo\Content\SocialImage;

class SocialImageRepository
{
    public function make(): SocialImage
    {
        return resolve(SocialImage::class);
    }
}
