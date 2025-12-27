<?php

namespace Aerni\AdvancedSeo\Models;

use Aerni\AdvancedSeo\Blueprints\AnalyticsBlueprint;
use Aerni\AdvancedSeo\Blueprints\ContentDefaultsBlueprint;
use Aerni\AdvancedSeo\Blueprints\FaviconsBlueprint;
use Aerni\AdvancedSeo\Blueprints\GeneralBlueprint;
use Aerni\AdvancedSeo\Blueprints\IndexingBlueprint;
use Aerni\AdvancedSeo\Blueprints\SocialMediaBlueprint;
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
    protected static array $siteDefaultsConfig = [
        'general' => [
            'title' => 'General',
            'blueprint' => GeneralBlueprint::class,
            'data' => 'general.yaml',
            'icon' => 'utilities',
        ],
        'indexing' => [
            'title' => 'Indexing',
            'blueprint' => IndexingBlueprint::class,
            'data' => 'indexing.yaml',
            'icon' => 'hierarchy',
        ],
        'social_media' => [
            'title' => 'Social Media',
            'blueprint' => SocialMediaBlueprint::class,
            'data' => 'social_media.yaml',
            'icon' => 'assets',
        ],
        'analytics' => [
            'title' => 'Analytics',
            'blueprint' => AnalyticsBlueprint::class,
            'data' => 'analytics.yaml',
            'icon' => 'money-graph-bar-increase',
            'enabled' => fn () => collect(config('advanced-seo.analytics'))
                ->reject(fn ($value, $key) => $key === 'environments')
                ->filter()
                ->isNotEmpty(),
        ],
        'favicons' => [
            'title' => 'Favicons',
            'blueprint' => FaviconsBlueprint::class,
            'data' => 'favicons.yaml',
            'icon' => 'edit-paint-palette',
            'enabled' => fn () => config('advanced-seo.favicons.enabled', false),
        ],
    ];

    protected static function getRows(): array
    {
        return Blink::once('advanced-seo::defaults::rows', function () {
            return collect(static::siteDefaults())
                ->merge(static::collectionDefaults())
                ->merge(static::taxonomyDefaults())
                ->toArray();
        });
    }

    protected static function siteDefaults(): Collection
    {
        return collect(static::$siteDefaultsConfig)->map(function ($config, $handle) {
            return [
                'id' => "site::{$handle}",
                'type' => 'site',
                'handle' => $handle,
                'title' => $config['title'],
                'blueprint' => $config['blueprint'],
                'data' => static::contentPath($config['data']),
                'enabled' => value($config['enabled'] ?? true),
                'icon' => $config['icon'],
                'type_icon' => 'web',
                'set' => Seo::findOrMake('site', $handle),
            ];
        });
    }

    protected static function collectionDefaults(): Collection
    {
        return CollectionFacade::all()->map(function ($collection) {
            return [
                'id' => "collections::{$collection->handle()}",
                'type' => 'collections',
                'handle' => $collection->handle(),
                'title' => $collection->title(),
                'blueprint' => ContentDefaultsBlueprint::class,
                'data' => static::contentPath('content.yaml'),
                'enabled' => true,
                'icon' => $collection->icon(),
                'type_icon' => 'collections',
                'set' => Seo::findOrMake('collections', $collection->handle()),
            ];
        })->sortBy('handle');
    }

    protected static function taxonomyDefaults(): Collection
    {
        return Taxonomy::all()->map(function ($taxonomy) {
            return [
                'id' => "taxonomies::{$taxonomy->handle()}",
                'type' => 'taxonomies',
                'handle' => $taxonomy->handle(),
                'title' => $taxonomy->title(),
                'blueprint' => ContentDefaultsBlueprint::class,
                'data' => static::contentPath('content.yaml'),
                'enabled' => true,
                'icon' => 'taxonomies',
                'type_icon' => 'taxonomies',
                'set' => Seo::findOrMake('taxonomies', $taxonomy->handle()),
            ];
        })->sortBy('handle');
    }

    protected static function contentPath(string $file): string
    {
        return __DIR__."/../../content/{$file}";
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
