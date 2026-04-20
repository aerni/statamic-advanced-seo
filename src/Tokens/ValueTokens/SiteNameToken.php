<?php

namespace Aerni\AdvancedSeo\Tokens\ValueTokens;

use Aerni\AdvancedSeo\Context\Context;
use Aerni\AdvancedSeo\Facades\Seo;
use Aerni\AdvancedSeo\Tokens\ValueToken;

class SiteNameToken extends ValueToken
{
    public function handle(): string
    {
        return 'site_name';
    }

    public function value(): ?string
    {
        return Seo::find('site::defaults')
            ->in(Context::from($this->parent)->site)
            ->value('site_name');
    }
}
