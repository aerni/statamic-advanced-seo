<?php

namespace Aerni\AdvancedSeo\GraphQL\Fields;

use Aerni\AdvancedSeo\Actions\IsEnabledModel;
use Aerni\AdvancedSeo\GraphQL\Types\SeoMetaType;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Field;
use Statamic\Contracts\Entries\Entry;
use Statamic\Contracts\Taxonomies\Term;
use Statamic\Facades\GraphQL;

class SeoField extends Field
{
    protected $attributes = [
        'name' => 'seo',
        'description' => 'The Advanced SEO meta data',
    ];

    public function type(): Type
    {
        return GraphQL::type(SeoMetaType::NAME);
    }

    protected function resolve(Entry|Term $model): Entry|Term|null
    {
        // Only resolve the data if the collection or taxonomy wasn't disabled in the config
        return IsEnabledModel::handle($model) ? $model : null;
    }
}
