<?php

namespace Aerni\AdvancedSeo\GraphQL\Types;

use Aerni\AdvancedSeo\Data\SeoSet;
use Aerni\AdvancedSeo\Data\SeoSetLocalization;
use Aerni\AdvancedSeo\Facades\Seo;
use Aerni\AdvancedSeo\GraphQL\Resolvers\SeoSetResolver;
use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Support\Str;
use Rebing\GraphQL\Support\Type;
use Statamic\Facades\GraphQL;

class SiteSetType extends Type
{
    const NAME = 'siteSet';

    protected $attributes = [
        'name' => self::NAME,
        'description' => 'The SEO set for the site',
    ];

    protected array $types = [
        'analytics' => AnalyticsSiteSetType::NAME,
        'favicons' => FaviconsSiteSetType::NAME,
        'general' => GeneralSiteSetType::NAME,
        'indexing' => IndexingSiteSetType::NAME,
        'social_media' => SocialMediaSiteSetType::NAME,
    ];

    public function fields(): array
    {
        return Seo::whereType('site')
            ->mapWithKeys(fn (SeoSet $set) => [
                Str::camel($set->handle()) => [
                    'type' => GraphQL::type($this->types[$set->handle()]),
                    'description' => "The {$set->title()} settings",
                    'resolve' => $this->resolver(),
                ],
            ])
            ->all();
    }

    private function resolver(): callable
    {
        return function ($root, $args, $context, ResolveInfo $info): ?SeoSetLocalization {
            return SeoSetResolver::resolve('site::'.Str::snake($info->fieldName), $root['site'] ?? null);
        };
    }
}
