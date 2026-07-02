<?php

namespace Aerni\AdvancedSeo\Redirects;

use Aerni\AdvancedSeo\Enums\ResponseCode;

class ResolvedRedirect
{
    public function __construct(
        public readonly ResponseCode $responseCode,
        public readonly ?string $destination,
        public readonly bool $forwardQueryString = true,
    ) {}
}
