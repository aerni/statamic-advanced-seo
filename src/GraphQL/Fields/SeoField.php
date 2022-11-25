<?php

namespace Aerni\AdvancedSeo\GraphQL\Fields;

use Statamic\Facades\GraphQL;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Field;
use Statamic\Contracts\Entries\Entry;
use Aerni\AdvancedSeo\Models\Defaults;
use Statamic\Contracts\Taxonomies\Term;
use Aerni\AdvancedSeo\View\GraphQlCascade;
use Statamic\Contracts\Entries\Collection;
use Statamic\Contracts\Taxonomies\Taxonomy;
use Aerni\AdvancedSeo\GraphQL\Types\SeoType;
use Aerni\AdvancedSeo\Actions\EvaluateModelParent;

class SeoField extends Field
{
    protected $attributes = [
        'description' => 'Get the seo meta data',
    ];

    public function type(): Type
    {
        return GraphQL::type(SeoType::NAME);
    }

    protected function resolve(Entry|Term $model): ?GraphQlCascade
    {
        $parent = EvaluateModelParent::handle($model);

        if ($parent instanceof Collection) {
            $id = "collections::{$parent->handle()}";
        }

        if ($parent instanceof Taxonomy) {
            $id = "taxonomies::{$parent->handle()}";
        }

        // Only return seo data if the collection or taxonomy wasn't disabled in the config
        return Defaults::isEnabled($id)
            ? GraphQlCascade::from($model)->process()
            : null;
    }
}
