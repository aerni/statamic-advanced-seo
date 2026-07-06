<?php

namespace Aerni\AdvancedSeo\Enums;

enum Origin: string
{
    case Automatic = 'automatic';
    case Manual = 'manual';
    case Import = 'import';
    case Error = 'error';

    public function label(): string
    {
        return __("advanced-seo::messages.redirect_origin_{$this->value}");
    }
}
