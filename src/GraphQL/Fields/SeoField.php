<?php

namespace Aerni\AdvancedSeo\GraphQL\Fields;

use Aerni\AdvancedSeo\Actions\EvaluateModelParent;
use Aerni\AdvancedSeo\GraphQL\Types\SeoType;
use Aerni\AdvancedSeo\Models\Defaults;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Field;
use Statamic\Contracts\Entries\Collection;
use Statamic\Contracts\Entries\Entry;
use Statamic\Contracts\Taxonomies\Taxonomy;
use Statamic\Contracts\Taxonomies\Term;
use Statamic\Facades\GraphQL;

class SeoField extends Field
{
    protected $attributes = [
        'description' => 'All the Advanced SEO data of an entry/term',
    ];

    public function type(): Type
    {
        return GraphQL::type(SeoType::NAME);
    }

    protected function resolve(Entry|Term $model): Entry|Term|Null
    {
        $parent = EvaluateModelParent::handle($model);

        if ($parent instanceof Collection) {
            $id = "collections::{$parent->handle()}";
        }

        if ($parent instanceof Taxonomy) {
            $id = "taxonomies::{$parent->handle()}";
        }

        // Only return seo data if the collection or taxonomy wasn't disabled in the config
        return Defaults::isEnabled($id) ? $model : null;
    }
}
