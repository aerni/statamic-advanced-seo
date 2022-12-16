<?php

namespace Aerni\AdvancedSeo\GraphQL\Types;

use Aerni\AdvancedSeo\Facades\Seo;
use Aerni\AdvancedSeo\Models\Defaults;
use GraphQL\Type\Definition\ResolveInfo;
use Rebing\GraphQL\Support\Type;
use Statamic\Facades\GraphQL;

class SeoDefaultsType extends Type
{
    const NAME = 'seoDefaults';

    protected $attributes = [
        'name' => self::NAME,
        'description' => 'The Advanced SEO site, collection, and taxonomy defaults',
    ];

    public function fields(): array
    {
        return [
            'site' => [
                'type' => GraphQL::type(SiteDefaultsType::NAME),
                'description' => 'The Advanced SEO site defaults',
                'args' => [
                    'site' => [
                        'type' => GraphQL::string(),
                    ],
                ],
                'resolve' => $this->siteDefaultsResolver(),
            ],
            'collections' => [
                'type' => GraphQL::type(ContentDefaultsType::NAME),
                'description' => 'The Advanced SEO collection defaults',
                'args' => [
                    'handle' => [
                        'name' => 'handle',
                        'type' => GraphQL::string(),
                        'rules' => ['required'],
                    ],
                    'site' => [
                        'type' => GraphQL::string(),
                    ],
                ],
                'resolve' => $this->contentDefaultsResolver(),
            ],
            'taxonomies' => [
                'type' => GraphQL::type(ContentDefaultsType::NAME),
                'description' => 'The Advanced SEO taxonomy defaults',
                'args' => [
                    'handle' => [
                        'name' => 'handle',
                        'type' => GraphQL::string(),
                        'rules' => ['required'],
                    ],
                    'site' => [
                        'type' => GraphQL::string(),
                    ],
                ],
                'resolve' => $this->contentDefaultsResolver(),
            ],
        ];
    }

    private function siteDefaultsResolver(): callable
    {
        return function ($queryArgs, $args, $context, ResolveInfo $info) {
            return $args;
        };
    }

    private function contentDefaultsResolver(): callable
    {
        return function ($queryArgs, $args, $context, ResolveInfo $info) {
            $set = Seo::find($info->fieldName, $args['handle']);

            if (! $set) {
                return null;
            }

            if (! $set->isEnabled()) {
                return null;
            }

            return array_has($args, 'site')
                ? $set->in($args['site'])
                : $set->inDefaultSite();
        };
    }
}
