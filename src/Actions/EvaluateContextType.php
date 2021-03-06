<?php

namespace Aerni\AdvancedSeo\Actions;

use Statamic\Stache\Query\TermQueryBuilder;
use Statamic\Tags\Context;

class EvaluateContextType
{
    public static function handle(Context $context): ?string
    {
        return match (true) {
            ($context->has('is_entry')) => 'entry',
            ($context->has('is_term')) => 'term',
            ($context->get('terms') instanceof TermQueryBuilder) => 'taxonomy',
            ($context->get('response_code') === 404) => 'error',
            default => null,
        };
    }
}
