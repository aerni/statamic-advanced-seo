<?php

namespace Aerni\AdvancedSeo\Enums;

enum ResponseCode: int
{
    case Permanent = 301;
    case Temporary = 302;
    case Gone = 410;

    public function label(): string
    {
        return __("advanced-seo::fields.redirect_response_code.option_{$this->value}");
    }
}
