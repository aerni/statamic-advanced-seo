<?php

namespace Aerni\AdvancedSeo\Tokens\ValueTokens;

use Aerni\AdvancedSeo\Context\Context;
use Aerni\AdvancedSeo\Facades\Seo;
use Aerni\AdvancedSeo\Tokens\ValueToken;

class SeparatorToken extends ValueToken
{
    public function handle(): string
    {
        return 'separator';
    }

    public function value(): ?string
    {
        return Seo::find('site::defaults')
            ->in(Context::from($this->parent)->site)
            ->value('separator');
    }
}
