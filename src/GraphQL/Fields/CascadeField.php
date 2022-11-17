<?php

namespace Aerni\AdvancedSeo\GraphQL\Fields;

use Aerni\AdvancedSeo\GraphQL\Types\CascadeType;
use Aerni\AdvancedSeo\View\GraphQlCascade;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Field;
use Statamic\Contracts\Entries\Entry;
use Statamic\Contracts\Taxonomies\Term;
use Statamic\Facades\GraphQL;

class CascadeField extends Field
{
    public function type(): Type
    {
        return GraphQL::type(CascadeType::NAME);
    }

    protected function resolve(Entry|Term $model)
    {
        return GraphQlCascade::from($model)->process();
    }
}
