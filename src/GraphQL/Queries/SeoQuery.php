<?php

namespace Aerni\AdvancedSeo\GraphQL\Queries;

use Aerni\AdvancedSeo\GraphQL\Types\SeoType;
use GraphQL\Type\Definition\Type;
use Statamic\Contracts\Entries\Entry;
use Statamic\Contracts\Taxonomies\Term;
use Statamic\Facades\Data;
use Statamic\Facades\GraphQL;
use Statamic\GraphQL\Queries\Query;

class SeoQuery extends Query
{
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
            'id' => [
                'name' => 'id',
                'type' => GraphQL::string(),
                'rules' => ['required'],
            ],
            'site' => GraphQL::string(),
        ];
    }

    public function resolve($root, $args): Entry|Term|Null
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

        return $model;
    }
}
