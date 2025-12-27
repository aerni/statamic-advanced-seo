<?php

namespace Aerni\AdvancedSeo\Models;

use Aerni\AdvancedSeo\Blueprints\AnalyticsBlueprint;
use Aerni\AdvancedSeo\Blueprints\ContentDefaultsBlueprint;
use Aerni\AdvancedSeo\Blueprints\FaviconsBlueprint;
use Aerni\AdvancedSeo\Blueprints\GeneralBlueprint;
use Aerni\AdvancedSeo\Blueprints\IndexingBlueprint;
use Aerni\AdvancedSeo\Blueprints\SocialMediaBlueprint;
use Aerni\AdvancedSeo\Data\SeoDefault;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Statamic\Facades\Blink;
use Statamic\Facades\Collection as CollectionFacade;
use Statamic\Facades\Taxonomy;
use Statamic\Facades\YAML;

class Defaults extends Registry
{
    protected static function siteDefaultsConfig(): array
    {
        return [
            'general' => [
                'title' => 'General',
                'blueprint' => GeneralBlueprint::class,
                'data' => 'general.yaml',
                'icon' => 'utilities',
                'enabled' => true,
            ],
            'indexing' => [
                'title' => 'Indexing',
                'blueprint' => IndexingBlueprint::class,
                'data' => 'indexing.yaml',
                'icon' => 'hierarchy',
                'enabled' => true,
            ],
            'social_media' => [
                'title' => 'Social Media',
                'blueprint' => SocialMediaBlueprint::class,
                'data' => 'social_media.yaml',
                'icon' => 'assets',
                'enabled' => true,
            ],
            'analytics' => [
                'title' => 'Analytics',
                'blueprint' => AnalyticsBlueprint::class,
                'data' => 'analytics.yaml',
                'icon' => 'money-graph-bar-increase',
                'enabled' => collect(config('advanced-seo.analytics'))
                    ->reject(fn ($value, $key) => $key === 'environments')
                    ->filter()
                    ->isNotEmpty(),
            ],
            'favicons' => [
                'title' => 'Favicons',
                'blueprint' => FaviconsBlueprint::class,
                'data' => 'favicons.yaml',
                'icon' => 'edit-paint-palette',
                'enabled' => config('advanced-seo.favicons.enabled', false),
            ],
        ];
    }

    protected static function make(): Collection
    {
        return collect(static::siteDefaults())
            ->merge(static::collectionDefaults())
            ->merge(static::taxonomyDefaults());
    }

    protected static function siteDefaults(): Collection
    {
        return collect(static::siteDefaultsConfig())->map(function ($config, $handle) {
            return new SeoDefault(
                type: 'site',
                handle: $handle,
                title: $config['title'],
                blueprint: $config['blueprint'],
                data: static::contentPath($config['data']),
                icon: $config['icon'],
                enabled: $config['enabled'],
            );
        });
    }

    protected static function collectionDefaults(): Collection
    {
        return CollectionFacade::all()->map(function ($collection) {
            return new SeoDefault(
                type: 'collections',
                handle: $collection->handle(),
                title: $collection->title(),
                blueprint: ContentDefaultsBlueprint::class,
                data: static::contentPath('content.yaml'),
                icon: $collection->icon(),
            );
        })->sortBy('handle');
    }

    protected static function taxonomyDefaults(): Collection
    {
        return Taxonomy::all()->map(function ($taxonomy) {
            return new SeoDefault(
                type: 'taxonomies',
                handle: $taxonomy->handle(),
                title: $taxonomy->title(),
                blueprint: ContentDefaultsBlueprint::class,
                data: static::contentPath('content.yaml'),
                icon: 'taxonomies',
            );
        })->sortBy('handle');
    }

    protected static function contentPath(string $file): string
    {
        return __DIR__."/../../content/{$file}";
    }

    protected static function data(string $id): Collection
    {
        return Blink::once("advanced-seo::defaults::data::$id", function () use ($id) {
            $model = static::all()->filter(fn ($row) => Str::contains($row->id(), $id))->first();
            $path = $model?->data;

            if (is_null($path)) {
                return collect();
            }

            return collect(YAML::file($path)->parse());
        });
    }

    protected static function blueprint(string $id): ?string
    {
        return static::all()->first(fn ($row) => $row->id() === $id)?->blueprint;
    }

    protected static function enabled(): Collection
    {
        return static::all()->filter(fn ($row) => $row->enabled());
    }

    protected static function enabledInType(string $type): Collection
    {
        return static::all()->filter(fn ($row) => $row->type === $type && $row->enabled());
    }

    protected static function isEnabled(string $id): bool
    {
        return static::all()->contains(fn ($row) => $row->id() === $id && $row->enabled());
    }
}
