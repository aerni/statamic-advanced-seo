<?php

namespace Aerni\AdvancedSeo\GraphQL\Types;

use Statamic\Facades\GraphQL;
use Rebing\GraphQL\Support\Type;
use Aerni\AdvancedSeo\Facades\Seo;
use Aerni\AdvancedSeo\Models\Defaults;
use GraphQL\Type\Definition\ResolveInfo;

class SiteDefaultsType extends Type
{
    const NAME = 'siteDefaults';

    protected $attributes = [
        'name' => self::NAME,
    ];

    public function fields(): array
    {
        $fields = [
            'analytics' => [
                'type' => GraphQL::type(AnalyticsDefaultsType::NAME),
                'resolve' => $this->resolver(),
            ],
            'favicons' => [
                'type' => GraphQL::type(FaviconsDefaultsType::NAME),
                'resolve' => $this->resolver(),
            ],
            'general' => [
                'type' => GraphQL::type(GeneralDefaultsType::NAME),
                'resolve' => $this->resolver(),
            ],
            'indexing' => [
                'type' => GraphQL::type(IndexingDefaultsType::NAME),
                'resolve' => $this->resolver(),
            ],
            'socialMedia' => [
                'type' => GraphQL::type(SocialMediaDefaultsType::NAME),
                'resolve' => $this->resolver(),
            ],
        ];

        return collect($fields)
            // We only want to make fields available, if the feature is enabled.
            ->filter(fn ($field, $handle) => Defaults::isEnabled('site::'.snake_case($handle)))
            ->all();
    }

    private function resolver(): callable
    {
        return function (array $queryArgs, $args, $context, ResolveInfo $info) {
            $set = Seo::find('site', snake_case($info->fieldName));

            if (! $set) {
                return null;
            }

            return array_has($queryArgs, 'site')
                ? $set->in($queryArgs['site'])
                : $set->inDefaultSite();
        };
    }
}
