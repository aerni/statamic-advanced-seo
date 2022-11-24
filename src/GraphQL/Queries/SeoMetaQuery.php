<?php

namespace Aerni\AdvancedSeo\GraphQL\Queries;

use Aerni\AdvancedSeo\GraphQL\Types\MetaType;
use Aerni\AdvancedSeo\View\GraphQlCascade;
use GraphQL\Type\Definition\Type;
use Statamic\Contracts\Entries\Entry;
use Statamic\Contracts\Taxonomies\Term;
use Statamic\Facades\Data;
use Statamic\Facades\GraphQL;
use Statamic\GraphQL\Queries\Concerns\FiltersQuery;
use Statamic\GraphQL\Queries\Query;

class SeoMetaQuery extends Query
{
    use FiltersQuery;

    protected $attributes = [
        'name' => 'seoMeta',
    ];

    public function type(): Type
    {
        return GraphQL::type(MetaType::NAME);
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
             * Have to explicitly return 'null' because the 'in' method returns
             * a new LocalizedTerm, even if none exists for the requested locale.
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
