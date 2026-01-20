<?php

namespace Aerni\AdvancedSeo\Features;

use Aerni\AdvancedSeo\Context\Context;

abstract class Feature
{
    abstract public static function enabled(?Context $context = null): bool;
}
