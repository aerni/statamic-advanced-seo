<?php

namespace Aerni\AdvancedSeo\Traits;

trait ValidateType
{
    protected function isValidType(string $type): bool
    {
        if (! in_array($type, $this->allowedTypes)) {
            return false;
        }

        return true;
    }
}
