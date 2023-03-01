<?php

namespace Aerni\AdvancedSeo\GraphQL\Queries;

use Aerni\AdvancedSeo\Actions\IsEnabledModel;
use Aerni\AdvancedSeo\GraphQL\Types\SeoMetaType;
use GraphQL\Type\Definition\Type;
use Statamic\Contracts\Entries\Entry;
use Statamic\Contracts\Taxonomies\Term;
use Statamic\Facades\Data;
use Statamic\Facades\GraphQL;
use Statamic\GraphQL\Queries\Query;

class SeoMetaQuery extends Query
{
    protected $attributes = [
        'name' => 'seoMeta',
        'description' => 'The Advanced SEO meta data',
    ];

    public function args(): array
    {
        return [
            'id' => [
                'name' => 'id',
                'type' => GraphQL::string(),
                'rules' => ['required'],
            ],
            'site' => [
                'type' => GraphQL::string(),
            ],
        ];
    }

    public function type(): Type
    {
        return GraphQL::type(SeoMetaType::NAME);
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

        // Only resolve the meta data if the collection or taxonomy wasn't disabled in the config
        return IsEnabledModel::handle($model) ? $model : null;
    }
}
