<?php

namespace Aerni\AdvancedSeo\Enums;

enum RedirectType: int
{
    case Permanent = 301;
    case Temporary = 302;
    case Gone = 410;
}
