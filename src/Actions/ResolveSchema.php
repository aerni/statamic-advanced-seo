<?php

namespace Aerni\AdvancedSeo\Actions;

use Aerni\AdvancedSeo\Facades\Seo;
use Aerni\AdvancedSeo\Support\Helpers;
use Statamic\Contracts\Entries\Entry;
use Statamic\Contracts\Taxonomies\Term;
use Statamic\Facades\Antlers;
use Statamic\Facades\Blink;
use Statamic\Facades\GlobalSet;
use Statamic\Facades\Site;
use Statamic\Sites\Site as SiteInstance;
use Statamic\Support\Str;
use Statamic\Tags\Context as ViewContext;
use Statamic\View\Cascade;

class ResolveSchema
{
    /**
     * Schema-producing keys removed from the variable set so a reference to
     * them resolves to empty instead of re-augmenting.
     *
     * @var array<int, string>
     */
    protected static array $schemaKeys = ['json_ld', 'site_json_ld', 'site_schema', 'page_schema'];

    public static function handle(?string $value, Entry|Term|ViewContext $model): ?string
    {
        if (! Str::contains($value, '{{')) {
            return $value;
        }

        $variables = match (true) {
            $model instanceof Entry,
            $model instanceof Term => static::contentVariables($model),
            $model instanceof ViewContext => static::contextVariables($model),
        };

        return Antlers::parse($value, $variables);
    }

    /**
     * Variables ordered from lowest to highest precedence, so the entry's own
     * data wins over config, globals and site defaults.
     *
     * @return array<string, mixed>
     */
    protected static function contentVariables(Entry|Term $model): array
    {
        $model = Helpers::localizedContent($model);

        return Blink::once("advanced-seo::schema-context::{$model->id()}::{$model->locale()}", fn () => [
            ...static::siteDefaults($model->site()),
            ...static::globals($model->site()),
            'config' => Cascade::config(),
            'current_url' => $model->absoluteUrl(),
            ...$model->toAugmentedArray(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected static function contextVariables(ViewContext $model): array
    {
        $site = $model->get('site') ?? Site::current();

        return [
            ...static::siteDefaults($site),
            ...static::globals($site),
            'config' => Cascade::config(),
            ...$model->all(),
        ];
    }

    /**
     * The site-level SEO defaults (e.g. site_name), de-prefixed. Content
     * defaults are excluded as they only serve to cascade into entry fields.
     *
     * @return array<string, mixed>
     */
    protected static function siteDefaults(SiteInstance $site): array
    {
        return Seo::find('site::defaults')->in($site->handle())
            ->toAugmentedCollection()
            ->map->value()
            ->mapWithKeys(fn ($value, $key) => [Str::remove('seo_', $key) => $value])
            ->except(static::$schemaKeys)
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    protected static function globals(SiteInstance $site): array
    {
        $variables = [];

        foreach (GlobalSet::all() as $set) {
            if (! $localized = $set->in($site->handle())) {
                continue;
            }

            if ($set->handle() === 'global') {
                $variables = array_merge($variables, $localized->toDeferredAugmentedArray());
            } else {
                $variables[$set->handle()] = $localized;
            }
        }

        return $variables;
    }
}
