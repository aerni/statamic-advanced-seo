<?php

namespace Aerni\AdvancedSeo\View;

use Aerni\AdvancedSeo\Blueprints\OnPageSeoBlueprint;
use Aerni\AdvancedSeo\Facades\Seo;
use Aerni\AdvancedSeo\Facades\SocialImage;
use Aerni\AdvancedSeo\Support\Helpers;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Spatie\SchemaOrg\Schema;
use Statamic\Entries\Entry;
use Statamic\Facades\Data;
use Statamic\Facades\Site;
use Statamic\Facades\URL;
use Statamic\Fields\Value;
use Statamic\Sites\Site as StatamicSite;
use Statamic\Stache\Query\TermQueryBuilder;
use Statamic\Support\Str;
use Statamic\Taxonomies\Taxonomy;

class Cascade
{
    protected Collection $context;
    protected StatamicSite $site;
    protected Collection $data;

    public function __construct(array $context)
    {
        $this->context = collect($context);
        $this->site = Site::current();
        $this->data = $this->data();
    }

    public static function make(array $context): self
    {
        return new static($context);
    }

    public function get(): array
    {
        return $this->data->merge($this->computedData())->sortKeys()->all();
    }

    public function data(): Collection
    {
        $this->siteDefaults = $this->siteDefaults();
        $this->onPageSeo = $this->onPageSeo();

        $data = $this->siteDefaults
            ->merge($this->onPageSeo)
            ->mapWithKeys(function ($item, $key) {
                return [Str::remove('seo_', $key) => $item];
            });

        return $this->ensureOverrides($data);
    }

    protected function computedData(): array
    {
        return [
            'title' => $this->compiledTitle(),
            'og_title' => $this->ogTitle(),
            'og_description' => $this->ogDescription(),
            'og_image_size' => $this->ogImageSize(),
            'twitter_title' => $this->twitterTitle(),
            'twitter_description' => $this->twitterDescription(),
            'twitter_image_size' => $this->twitterImageSize(),
            'indexing' => $this->indexing(),
            'locale' => $this->locale(),
            'hreflang' => $this->hreflang(),
            'canonical' => $this->canonical(),
            'schema' => $this->schema(),
            'breadcrumbs' => $this->breadcrumbs(),
        ];
    }

    /**
     * Only return values that are not empty and whose keys exists in the Blueprint.
     * This makes sure that we don't return any data of fields that were disabled in the config, e.g. Social Images Generator
     */
    protected function onPageSeo(): Collection
    {
        return $this->context
            ->intersectByKeys(OnPageSeoBlueprint::make()->items())
            ->filter(function ($item) {
                return $item instanceof Value && $item->raw();
            });
    }

    /**
     * Get the augmented site defaults and filter out any values that shouldn't be there
     * like features that were disabled in the config.
     */
    protected function siteDefaults(): Collection
    {
        return Seo::allOfType('site')->flatMap(function ($defaults) {
            return $defaults->in($this->site->handle())->toAugmentedArray();
        })->filter(function ($item) {
            return $item instanceof Value;
        });
    }

    protected function ensureOverrides(Collection $data): Collection
    {
        return $data->merge([
            'noindex' => optional($this->siteDefaults->get('noindex'))->value() ?: optional($data->get('noindex'))->value(),
            'nofollow' => optional($this->siteDefaults->get('nofollow'))->value() ?: optional($data->get('nofollow'))->value(),
        ]);
    }

    protected function compiledTitle(): string
    {
        return $this->titlePosition() === 'before'
            ? "{$this->title()} {$this->titleSeparator()} {$this->siteName()}"
            : "{$this->siteName()} {$this->titleSeparator()} {$this->title()}";
    }

    protected function title(): string
    {
        if ($this->context->get('response_code') === 404) {
            return '404';
        }

        return $this->data->get('title') ?? $this->context->get('title');
    }

    protected function titleSeparator(): string
    {
        return $this->data->get('title_separator') ?? '|';
    }

    protected function titlePosition(): string
    {
        return $this->data->get('title_position') ?? 'before';
    }

    protected function siteName(): string
    {
        return $this->data->get('site_name') ?? config('app.name');
    }

    protected function ogTitle(): string
    {
        return $this->data->get('og_title') ?? $this->title();
    }

    protected function ogDescription(): ?string
    {
        return $this->data->get('og_description') ?? $this->data->get('description');
    }

    protected function ogImageSize(): array
    {
        return collect(SocialImage::types()->get('og'))
            ->only(['width', 'height'])
            ->all();
    }

    protected function twitterTitle(): string
    {
        return $this->data->get('twitter_title') ?? $this->title();
    }

    protected function twitterDescription(): ?string
    {
        return $this->data->get('twitter_description') ?? $this->data->get('description');
    }

    protected function twitterImageSize(): array
    {
        return collect(SocialImage::types()->get('twitter'))
            ->only(['width', 'height'])
            ->all();
    }

    protected function indexing(): string
    {
        return collect([
            'noindex' => $this->data->get('noindex'),
            'nofollow' => $this->data->get('nofollow'),
        ])->filter()->keys()->implode(', ');
    }

    protected function locale(): string
    {
        return Helpers::parseLocale($this->site->locale());
    }

    // TODO: Support collection taxonomy details page and collection term details page.
    protected function hreflang(): ?array
    {
        /*
        Return if we're on a collection taxonomy details page.
        Statamic has yet to provide a way to get the URLs of collection taxonomies.
        */
        if ($this->context->has('segment_2') && $this->context->get('terms') instanceof TermQueryBuilder) {
            return null;
        }

        /*
        Return if we're on a collection term details page.
        Statamic has yet to provide a way to get the URLs of collection terms.
        */
        if ($this->context->has('segment_3') && $this->context->get('is_term') === true) {
            return null;
        }

        // Handles global taxonomy details page.
        if ($this->context->has('segment_1') && $this->context->get('terms') instanceof TermQueryBuilder) {
            $taxonomy = $this->context->get('terms')->first()->taxonomy();

            $data = $taxonomy->sites()->map(function ($locale) use ($taxonomy) {
                // Set the current site so we can get the localized absolute URLs of the taxonomy.
                Site::setCurrent($locale);

                return [
                    'url' => $taxonomy->absoluteUrl(),
                    'locale' => Helpers::parseLocale(Site::get($locale)->locale()),
                ];
            })->toArray();


            // Reset the site to the original.
            Site::setCurrent($this->site->handle());

            return $data;
        }

        // Handle entries and global term details page.
        $data = Data::find($this->context->get('id'));

        if (! $data) {
            return null;
        }

        $sites = $data instanceof Entry
            ? $data->sites()
            : $data->taxonomy()->sites();

        // We only want to return data for published entries and terms.
        $alternates = $sites->filter(function ($locale) use ($data) {
            return optional($data->in($locale))->published();
        })->values();

        return $alternates->map(function ($locale) use ($data) {
            return [
                'url' => $data->in($locale)->absoluteUrl(),
                'locale' => Helpers::parseLocale(Site::get($locale)->locale()),
            ];
        })->toArray();
    }

    protected function canonical(): ?string
    {
        $type = optional($this->data->get('canonical_type'))->raw();

        if ($type === 'other') {
            return config('app.url') . optional($this->data->get('canonical_entry')->value())->url();
        }

        if ($type === 'custom') {
            return $this->data->get('canonical_custom')->raw();
        }

        $page = Arr::get($this->context->get('get'), 'page');
        $currentUrl = $this->context->get('current_url');

        return $page ? "{$currentUrl}?page={$page}" : $currentUrl;
    }

    protected function schema(): ?string
    {
        $schema = $this->siteSchema() . $this->entrySchema();

        return ! empty($schema) ? $schema : null;
    }

    protected function siteSchema(): ?string
    {
        $type = optional($this->data->get('site_json_ld_type'))->raw();

        if (empty($type) || $type === 'none') {
            return null;
        }

        if ($type === 'custom') {
            $data = $this->data->get('site_json_ld')->value()->value();

            return $data
                ? '<script type="application/ld+json">' . $data . '</script>'
                : null;
        }

        if ($type === 'organization') {
            $schema = Schema::organization()
                ->name($this->data->get('organization_name')->value())
                ->url(config('app.url') . $this->context->get('homepage'));

            if ($logo = optional($this->data->get('organization_logo'))->value()) {
                $logo = Schema::imageObject()
                    ->url($logo->absoluteUrl())
                    ->width($logo->width())
                    ->height($logo->height());

                $schema->logo($logo);
            }
        }

        if ($type === 'person') {
            $schema = Schema::person()
                ->name($this->data->get('person_name')->value())
                ->url(config('app.url') . $this->context->get('homepage'));
        }

        return $schema->toScript();
    }

    protected function entrySchema(): ?string
    {
        $data = optional($this->data->get('json_ld'))->value()->value();

        return $data
            ? '<script type="application/ld+json">' . $data . '</script>'
            : null;
    }

    protected function breadcrumbs(): ?string
    {
        $enabled = optional($this->data->get('breadcrumbs'))->value();
        $isHome = $this->context->get('url', '') === '/';

        if ($enabled && ! $isHome) {
            $listItems = $this->breadcrumbsListItems()->map(function ($crumb, $key) {
                $item = Schema::thing()->setProperty('id', $crumb->absoluteUrl());

                if ($crumb instanceof Taxonomy) {
                    $item->name($crumb->title());
                } elseif ($title = $crumb->get('title') ?? $crumb->origin()?->get('title')) {
                    $item->name($title);
                }

                return Schema::listItem()->position($key + 1)->item($item);
            });

            return Schema::breadcrumbList()->itemListElement($listItems);
        }

        return null;
    }

    public function breadcrumbsListItems(): Collection
    {
        $url = URL::makeAbsolute(URL::getCurrent());
        $url = Str::removeLeft($url, Site::current()->absoluteUrl());
        $url = Str::ensureLeft($url, '/');

        $segments = explode('/', $url);
        $segments[0] = '/';

        $crumbs = collect($segments)->map(function () use (&$segments) {
            $uri = URL::tidy(join('/', $segments));
            array_pop($segments);

            return $uri;
        })->mapWithKeys(function ($uri) {
            $uri = Str::ensureLeft($uri, '/');

            return [$uri => Data::findByUri($uri, Site::current()->handle())];
        })->filter();

        return $crumbs->reverse()->values();
    }
}
