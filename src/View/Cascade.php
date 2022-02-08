<?php

namespace Aerni\AdvancedSeo\View;

use Aerni\AdvancedSeo\Concerns\GetsContentDefaults;
use Aerni\AdvancedSeo\Concerns\GetsPageData;
use Aerni\AdvancedSeo\Concerns\GetsSiteDefaults;
use Aerni\AdvancedSeo\Facades\SocialImage;
use Aerni\AdvancedSeo\Support\Helpers;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Spatie\SchemaOrg\Schema;
use Statamic\Contracts\Entries\Entry;
use Statamic\Contracts\Taxonomies\Term;
use Statamic\Facades\Data;
use Statamic\Facades\Site;
use Statamic\Facades\URL;
use Statamic\Fields\Value;
use Statamic\Stache\Query\TermQueryBuilder;
use Statamic\Support\Str;
use Statamic\Taxonomies\Taxonomy;

class Cascade
{
    use GetsContentDefaults;
    use GetsSiteDefaults;
    use GetsPageData;

    protected Collection $data;

    public function __construct(protected Entry|Term|Collection $context)
    {
        $this->data = collect();
    }

    public static function from(Entry|Term|Collection $context): self
    {
        return new static($context);
    }

    public function withSiteDefaults(): self
    {
        $siteDefaults = $this->getSiteDefaults($this->context);

        $this->data = $this->data->merge($siteDefaults);

        return $this;
    }

    public function withContentDefaults(): self
    {
        $contentDefaults = $this->getContentDefaults($this->context);
        $contentDefaults = $this->removeSeoPrefixFromKeys($contentDefaults);

        $this->data = $this->data->merge($contentDefaults);

        return $this;
    }

    public function withPageData(): self
    {
        $pageData = $this->getPageData($this->context);
        $pageData = $this->removeSeoPrefixFromKeys($pageData);

        $this->data = $this->data->merge($pageData);

        return $this;
    }

    public function get(): array
    {
        return $this->data->all();
    }

    public function value(string $key): mixed
    {
        $value = $this->data->get($key);

        return $value instanceof Value ? $value->raw() : $value;
    }

    public function processForFrontend(): self
    {
        return $this
            ->ensureOverrides()
            ->withComputedData()
            ->applyWhitelist()
            ->sortKeys();
    }

    protected function removeSeoPrefixFromKeys(Collection $data): Collection
    {
        return $data->mapWithKeys(fn ($item, $key) => [Str::remove('seo_', $key) => $item]);
    }

    protected function ensureOverrides(): self
    {
        $overrides = $this->getSiteDefaults($this->context)
            ->only(['noindex', 'nofollow'])
            ->filter(fn ($item) => $item->raw());

        $this->data = $this->data->merge($overrides);

        return $this;
    }

    protected function withComputedData(): self
    {
        $computedData = collect([
            'title' => $this->compiledTitle(),
            'og_title' => $this->ogTitle(),
            'og_description' => $this->ogDescription(),
            'og_image_size' => $this->ogImageSize(),
            'twitter_card' => $this->twitterCard(),
            'twitter_title' => $this->twitterTitle(),
            'twitter_description' => $this->twitterDescription(),
            'twitter_image' => $this->twitterImage(),
            'twitter_image_size' => $this->twitterImageSize(),
            'indexing' => $this->indexing(),
            'locale' => $this->locale(),
            'hreflang' => $this->hreflang(),
            'canonical' => $this->canonical(),
            'schema' => $this->schema(),
            'breadcrumbs' => $this->breadcrumbs(),
        ])->filter();

        $this->data = $this->data->merge($computedData);

        return $this;
    }

    protected function applyWhitelist(): self
    {
        // Remove all the keys from the data that won't be used in any view on the frontend.
        $this->data = $this->data->only([
            'use_fathom',
            'fathom_domain',
            'fathom_id',
            'fathom_spa',
            'use_cloudflare_web_analytics',
            'cloudflare_web_analytics',
            'use_google_tag_manager',
            'google_tag_manager',
            'title',
            'description',
            'canonical',
            'favicon_svg',
            'hreflang',
            'indexing',
            'schema',
            'breadcrumbs',
            'site_name',
            'locale',
            'og_title',
            'og_description',
            'og_image',
            'generate_social_images',
            'og_image_size',
            'google_site_verification_code',
            'bing_site_verification_code',
            'twitter_card',
            'twitter_title',
            'twitter_description',
            'twitter_handle',
            'twitter_image',
            'twitter_image_size',
        ]);

        return $this;
    }

    protected function sortKeys(): self
    {
        $this->data = $this->data->sortKeys();

        return $this;
    }

    protected function compiledTitle(): string
    {
        $titlePosition = $this->data->get('title_position')?->raw() ?? 'before';

        return $titlePosition === 'before'
            ? "{$this->title()} {$this->titleSeparator()} {$this->siteName()}"
            : "{$this->siteName()} {$this->titleSeparator()} {$this->title()}";
    }

    protected function title(): Value|string
    {
        if ($this->context->get('response_code') === 404) {
            return '404';
        }

        return $this->data->get('title') ?? $this->context->get('title');
    }

    protected function titleSeparator(): Value|string
    {
        return $this->data->get('title_separator') ?? '|';
    }

    protected function siteName(): Value|string
    {
        return $this->data->get('site_name') ?? config('app.name');
    }

    protected function ogTitle(): Value|string
    {
        return $this->data->get('og_title') ?? $this->title();
    }

    protected function ogDescription(): ?Value
    {
        return $this->data->get('og_description') ?? $this->data->get('description');
    }

    protected function ogImageSize(): array
    {
        return collect(SocialImage::specs('og'))
            ->only(['width', 'height'])
            ->all();
    }

    protected function twitterCard(): Value|string
    {
        return $this->data->get('twitter_card') ?? 'summary';
    }

    protected function twitterTitle(): Value|string
    {
        return $this->data->get('twitter_title') ?? $this->title();
    }

    protected function twitterDescription(): ?Value
    {
        return $this->data->get('twitter_description') ?? $this->data->get('description');
    }

    protected function twitterImage(): ?Value
    {
        // Get the image if it exists on the entry or term.
        if ($image = $this->data->get('twitter_image')) {
            return $image;
        }

        // Get the image from the site defaults that matches the content's twitter card setting.
        return $this->data->first(fn ($value, $key) => str_contains($key, $this->twitterCard()));
    }

    protected function twitterImageSize(): array
    {
        return collect(SocialImage::specs("twitter.{$this->twitterCard()}"))
            ->only(['width', 'height'])
            ->all();
    }

    protected function indexing(): string
    {
        return collect([
            'noindex' => $this->value('noindex'),
            'nofollow' => $this->value('nofollow'),
        ])->filter()->keys()->implode(', ');
    }

    protected function locale(): string
    {
        return Helpers::parseLocale(Site::current()->locale());
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

            $initialSite = Site::current()->handle();

            $data = $taxonomy->sites()->map(function ($locale) use ($taxonomy) {
                // Set the current site so we can get the localized absolute URLs of the taxonomy.
                Site::setCurrent($locale);

                return [
                    'url' => $taxonomy->absoluteUrl(),
                    'locale' => Helpers::parseLocale(Site::get($locale)->locale()),
                ];
            })->toArray();


            // Reset the site to the original.
            Site::setCurrent($initialSite);

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
            return $data->in($locale)?->published();
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
        $type = $this->data->get('canonical_type')?->raw();

        if ($type === 'other') {
            return config('app.url') . $this->data->get('canonical_entry')?->value()?->url();
        }

        if ($type === 'custom') {
            return $this->data->get('canonical_custom')?->raw();
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
        $type = $this->data->get('site_json_ld_type')?->raw();

        if (empty($type) || $type === 'none') {
            return null;
        }

        if ($type === 'custom') {
            $data = $this->data->get('site_json_ld')?->value()?->value();

            return $data
                ? '<script type="application/ld+json">' . $data . '</script>'
                : null;
        }

        if ($type === 'organization') {
            $schema = Schema::organization()
                ->name($this->data->get('organization_name')->value())
                ->url(config('app.url') . $this->context->get('homepage'));

            if ($logo = $this->data->get('organization_logo')?->value()) {
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
        $data = $this->data->get('json_ld')?->value()?->value();

        return $data
            ? '<script type="application/ld+json">' . $data . '</script>'
            : null;
    }

    protected function breadcrumbs(): ?string
    {
        $enabled = $this->data->get('breadcrumbs')?->value();
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

    protected function breadcrumbsListItems(): Collection
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
