<?php

namespace Aerni\AdvancedSeo\GraphQL;

use Aerni\AdvancedSeo\Actions\GetDefaultsData;
use Aerni\AdvancedSeo\View\Cascade;
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

    protected function resolve(Entry $entry): array
    {
        rd(GetDefaultsData::handle($entry), $entry);

        // return Cascade::from(GetDefaultsData::handle($entry))->processForFrontend();
    }
}
