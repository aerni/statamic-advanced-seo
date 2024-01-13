<?php

namespace Aerni\AdvancedSeo\Models;

use Aerni\AdvancedSeo\Facades\Seo;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Statamic\Facades\Blink;
use Statamic\Facades\Collection as CollectionFacade;
use Statamic\Facades\Taxonomy;
use Statamic\Facades\YAML;

class Defaults extends Model
{
    protected static function getRows(): array
    {
        $site = [
            [
                'id' => 'site::general',
                'type' => 'site',
                'handle' => 'general',
                'title' => 'General',
                'blueprint' => \Aerni\AdvancedSeo\Blueprints\GeneralBlueprint::class,
                'data' => __DIR__.'/../../content/general.yaml',
                'enabled' => true,
                'icon' => 'icons/light/sites',
                'type_icon' => 'earth',
                'set' => Seo::findOrMake('site', 'general'),
            ],
            [
                'id' => 'site::indexing',
                'type' => 'site',
                'handle' => 'indexing',
                'title' => 'Indexing',
                'blueprint' => \Aerni\AdvancedSeo\Blueprints\IndexingBlueprint::class,
                'data' => __DIR__.'/../../content/indexing.yaml',
                'enabled' => true,
                'icon' => 'icons/light/structures',
                'type_icon' => 'earth',
                'set' => Seo::findOrMake('site', 'indexing'),
            ],
            [
                'id' => 'site::social_media',
                'type' => 'site',
                'handle' => 'social_media',
                'title' => 'Social Media',
                'blueprint' => \Aerni\AdvancedSeo\Blueprints\SocialMediaBlueprint::class,
                'data' => __DIR__.'/../../content/social_media.yaml',
                'enabled' => true,
                'icon' => 'icons/light/assets',
                'type_icon' => 'earth',
                'set' => Seo::findOrMake('site', 'social_media'),
            ],
            [
                'id' => 'site::analytics',
                'type' => 'site',
                'handle' => 'analytics',
                'title' => 'Analytics',
                'blueprint' => \Aerni\AdvancedSeo\Blueprints\AnalyticsBlueprint::class,
                'data' => __DIR__.'/../../content/analytics.yaml',
                'enabled' => collect(config('advanced-seo.analytics'))->reject(fn ($value, $key) => $key === 'environments')->filter()->isNotEmpty(),
                'icon' => 'icons/light/charts',
                'type_icon' => 'earth',
                'set' => Seo::findOrMake('site', 'analytics'),
            ],
            [
                'id' => 'site::favicons',
                'type' => 'site',
                'handle' => 'favicons',
                'title' => 'Favicons',
                'blueprint' => \Aerni\AdvancedSeo\Blueprints\FaviconsBlueprint::class,
                'data' => __DIR__.'/../../content/favicons.yaml',
                'enabled' => config('advanced-seo.favicons.enabled', false),
                'icon' => 'icons/light/color',
                'type_icon' => 'earth',
                'set' => Seo::findOrMake('site', 'favicons'),
            ],
        ];

        $collections = CollectionFacade::all()->map(function ($collection) {
            return [
                'id' => 'collections::'.$collection->handle(),
                'type' => 'collections',
                'handle' => $collection->handle(),
                'title' => $collection->title(),
                'blueprint' => \Aerni\AdvancedSeo\Blueprints\ContentDefaultsBlueprint::class,
                'data' => __DIR__.'/../../content/content.yaml',
                'enabled' => ! in_array($collection->handle(), config('advanced-seo.disabled.collections', [])),
                'icon' => 'icons/light/content-writing',
                'type_icon' => 'content-writing',
                'set' => Seo::findOrMake('collections', $collection->handle()),
            ];
        })->sortBy('handle');

        $taxonomies = Taxonomy::all()->map(function ($taxonomy) {
            return [
                'id' => 'taxonomies::'.$taxonomy->handle(),
                'type' => 'taxonomies',
                'handle' => $taxonomy->handle(),
                'title' => $taxonomy->title(),
                'blueprint' => \Aerni\AdvancedSeo\Blueprints\ContentDefaultsBlueprint::class,
                'data' => __DIR__.'/../../content/content.yaml',
                'enabled' => ! in_array($taxonomy->handle(), config('advanced-seo.disabled.taxonomies', [])),
                'icon' => 'icons/light/tags',
                'type_icon' => 'tags',
                'set' => Seo::findOrMake('taxonomies', $taxonomy->handle()),
            ];
        })->sortBy('handle');

        return collect($site)->merge($collections)->merge($taxonomies)->toArray();
    }

    protected static function all(): Collection
    {
        return static::$rows;
    }

    protected static function data(string $id): Collection
    {
        return Blink::once("advanced-seo::defaults::data::$id", function () use ($id) {
            $model = static::$rows->filter(fn ($row) => Str::contains($row['id'], $id))->first();
            $path = Arr::get($model, 'data');

            if (is_null($path)) {
                return collect();
            }

            return collect(YAML::file($path)->parse());
        });
    }

    protected static function blueprint(string $id): ?string
    {
        return static::$rows->firstWhere('id', $id)['blueprint'] ?? null;
    }

    protected static function enabled(): Collection
    {
        return static::$rows->where('enabled', true);
    }

    protected static function enabledInType(string $type): Collection
    {
        return static::$rows->where('type', $type)->where('enabled', true);
    }

    protected static function isEnabled(string $id): bool
    {
        return static::$rows->where('id', $id)->where('enabled', true)->isNotEmpty();
    }
}
