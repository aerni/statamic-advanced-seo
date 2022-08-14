<?php

namespace Aerni\AdvancedSeo\GraphQL;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Field;
use Statamic\Contracts\Entries\Entry;
use Statamic\Facades\GraphQL;

class AdvancedSeoField extends Field
{
    protected $attributes = [
        'description' => 'Get the Advanced SEO meta data',
    ];

    public function type(): Type
    {
        return GraphQL::type(AdvancedSeoType::NAME);
    }

    protected function resolve(Entry $entry)
    {
        return $entry;
    }
}
