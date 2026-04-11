<?php

namespace Aerni\AdvancedSeo\Scopes;

use Statamic\Query\Scopes\Scope;

class RoutableEntries extends Scope
{
    public function apply($query, $values): void
    {
        $query->whereNotNull('uri');
    }
}
