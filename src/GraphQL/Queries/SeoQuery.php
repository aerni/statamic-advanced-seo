<?php

namespace Aerni\AdvancedSeo\GraphQL\Queries;

use Statamic\Facades\Data;
use Statamic\Facades\GraphQL;
use GraphQL\Type\Definition\Type;
use Statamic\GraphQL\Queries\Query;
use Statamic\Contracts\Entries\Entry;
use Statamic\Contracts\Taxonomies\Term;
use Aerni\AdvancedSeo\View\GraphQlCascade;
use Aerni\AdvancedSeo\GraphQL\Types\SeoType;
use Aerni\AdvancedSeo\GraphQL\Types\MetaType;
use Statamic\GraphQL\Queries\Concerns\FiltersQuery;

class SeoQuery extends Query
{
    use FiltersQuery;

    protected $attributes = [
        'name' => 'seo',
    ];

    public function type(): Type
    {
        return GraphQL::type(SeoType::NAME);
    }

    public function args(): array
    {
        return [
            'id' => GraphQL::string(),
            'site' => GraphQL::string(),
        ];
    }

    public function resolve($root, $args)
    {
        $model = Data::find($args['id']);

        $site = $args['site'] ?? null;

        if ($site && $model instanceof Entry) {
            $model = $model->in($site);
        }

        if ($site && $model instanceof Term) {
            $locales = $model->term()->localizations()->keys();
            $termExistsInLocale = $locales->contains($site);

            /**
             * We have to explicitly return 'null' because the 'in' method returns
             * a new LocalizedTerm, even if this term doesn't exists in the requested locale.
             * This is different to how the 'in' method works on an Entry.
             */
            $model = $termExistsInLocale ? $model->in($site) : null;
        }

        if (! $model) {
            return null;
        }

        return GraphQlCascade::from($model)->process();
    }
}
