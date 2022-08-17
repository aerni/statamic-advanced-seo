<?php

namespace Aerni\AdvancedSeo\GraphQL\Fields;

use Aerni\AdvancedSeo\Actions\EvaluateModelParent;
use Aerni\AdvancedSeo\GraphQL\Types\OnPageSeoType;
use Aerni\AdvancedSeo\Models\Defaults;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Field;
use Statamic\Contracts\Entries\Collection;
use Statamic\Contracts\Entries\Entry;
use Statamic\Contracts\Taxonomies\Taxonomy;
use Statamic\Contracts\Taxonomies\Term;
use Statamic\Facades\GraphQL;

class OnPageSeoField extends Field
{
    protected $attributes = [
        'description' => 'Get the Advanced SEO meta data',
    ];

    public function type(): Type
    {
        return GraphQL::type(OnPageSeoType::NAME);
    }

    protected function resolve(Entry|Term $model)
    {
        $parent = EvaluateModelParent::handle($model);

        if ($parent instanceof Collection) {
            $id = "collections::{$parent->handle()}";
        }

        if ($parent instanceof Taxonomy) {
            $id = "taxonomies::{$parent->handle()}";
        }

        // Only return seo data if the collection or taxonomy wasn't disabled in the config.
        return Defaults::isEnabled($id) ? $model : null;
    }
}
