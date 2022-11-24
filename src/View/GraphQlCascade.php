<?php

namespace Aerni\AdvancedSeo\View;

use Aerni\AdvancedSeo\Facades\SocialImage;
use Aerni\AdvancedSeo\Models\Defaults;
use Aerni\AdvancedSeo\Support\Helpers;
use Illuminate\Support\Collection;
use Spatie\SchemaOrg\Schema;
use Statamic\Contracts\Entries\Entry;
use Statamic\Contracts\Taxonomies\Term;
use Statamic\Facades\Blink;
use Statamic\Facades\Data;
use Statamic\Facades\Site;
use Statamic\Facades\URL;
use Statamic\Fields\Value;
use Statamic\Stache\Query\TermQueryBuilder;
use Statamic\Support\Str;

class GraphQlCascade extends BaseCascade
{
    public function __construct(Entry|Term $model)
    {
        parent::__construct($model);
    }

    public function process(): self
    {
        return $this
            ->withSiteDefaults()
            ->withPageData()
            ->removeSeoPrefix()
            ->removeSectionFields()
            ->ensureOverrides()
            ->processComputedData()
            ->sortKeys();
    }

    public static function whitelist(): array
    {
        return [
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
            'prev_url',
            'next_url',
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
        ];
    }

    public function processComputedData(): self
    {
        $this->data = $this->data->merge([
            'title' => $this->title(),
            // 'og_image' => $this->ogImage(),
            // 'og_image_size' => $this->ogImageSize(),
            // 'twitter_card' => $this->twitterCard(), // TODO: Delete
            // 'twitter_image' => $this->twitterImage(),
            // 'twitter_image_size' => $this->twitterImageSize(),
            'twitter_handle' => $this->twitterHandle(),
            'indexing' => $this->indexing(),
            'locale' => $this->locale(),
            'hreflang' => $this->hreflang(),
            'canonical' => $this->canonical(),
            'schema' => $this->schema(),
            'breadcrumbs' => $this->breadcrumbs(),
        ])->filter();

        // dd($this->data);

        return $this;
    }

    protected function title(): string
    {
        $siteNamePosition = $this->value('site_name_position');
        $title = $this->get('title');
        $titleSeparator = $this->get('title_separator');
        $siteName = $this->get('site_name') ?? config('app.name');

        return match (true) {
            ($siteNamePosition == 'end') => "{$title} {$titleSeparator} {$siteName}",
            ($siteNamePosition == 'start') => "{$siteName} {$titleSeparator} {$title}",
            ($siteNamePosition == 'disabled') => $title,
            default => "{$title} {$titleSeparator} {$siteName}",
        };
    }

    protected function ogImage(): ?Value
    {
        return $this->value('generate_social_images')
            ? $this->get('generated_og_image')
            : $this->get('og_image');
    }

    protected function ogImageSize(): ?array
    {
        if (! $this->ogImage()) {
            return null;
        }

        return collect(SocialImage::findModel('open_graph'))
            ->only(['width', 'height'])
            ->all();
    }

    // TODO: Delete
    // protected function twitterCard(): string
    // {
    //     if ($card = $this->get('twitter_card')) {
    //         return $card;
    //     }

    //     /**
    //      * Determine the twitter card based on the images set in the social media defaults.
    //      * This is used on taxonomy and error pages.
    //      */
    //     $image = $this->get('twitter_summary_large_image') ?? $this->get('twitter_summary_image');

    //     return $image?->field()?->config()['twitter_card'] ?? Defaults::data('collections')->get('seo_twitter_card');
    // }

    protected function twitterImage(): ?Value
    {
        if (! $model = SocialImage::findModel("twitter_{$this->twitterCard()}")) {
            return null;
        }

        return $this->value('generate_social_images')
            ? $this->get('generated_twitter_image')
            : $this->get($model['handle']);
    }

    protected function twitterImageSize(): ?array
    {
        if (! $this->twitterImage()) {
            return null;
        }

        return collect(SocialImage::findModel("twitter_{$this->twitterCard()}"))
            ->only(['width', 'height'])
            ->all();
    }

    protected function twitterHandle(): ?string
    {
        $twitterHandle = $this->value('twitter_handle');

        return $twitterHandle ? Str::start($twitterHandle, '@') : null;
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
        return Helpers::parseLocale($this->model->site()->locale());
    }

    protected function hreflang(): ?array
    {
        $sites = $this->model instanceof Entry
            ? $this->model->sites()
            : $this->model->taxonomy()->sites();

        // We only want to return data for published entries and terms.
        $alternates = $sites->filter(fn ($locale) => $this->model->in($locale)?->published())->values();

        $hreflang = $alternates->map(fn ($locale) => [
            'url' => $this->model->in($locale)->absoluteUrl(),
            'locale' => Helpers::parseLocale(Site::get($locale)->locale()),
        ])->toArray();

        return $hreflang;
    }

    protected function canonical(): ?string
    {
        // We don't want to output a canonical tag if noindex is true.
        if ($this->value('noindex')) {
            return null;
        }

        $type = $this->value('canonical_type');

        return match (true) {
            ($type == 'other') => $this->value('canonical_entry')?->absoluteUrl(),
            ($type == 'custom') => $this->value('canonical_custom'),
            default => $this->model->absoluteUrl(),
        };
    }

    protected function schema(): ?string
    {
        $schema = $this->siteSchema().$this->entrySchema();

        return ! empty($schema) ? $schema : null;
    }

    protected function siteSchema(): ?string
    {
        $type = $this->value('site_json_ld_type');

        if ($type == 'none') {
            return null;
        }

        if ($type == 'custom') {
            $data = $this->value('site_json_ld')?->value();

            return $data
                ? '<script type="application/ld+json">'.$data.'</script>'
                : null;
        }

        if ($type == 'organization') {
            $schema = Schema::organization()
                ->name($this->value('organization_name'))
                ->url($this->model->site()->absoluteUrl());

            if ($logo = $this->value('organization_logo')) {
                $logo = Schema::imageObject()
                    ->url($logo->absoluteUrl())
                    ->width($logo->width())
                    ->height($logo->height());

                $schema->logo($logo);
            }
        }

        if ($type == 'person') {
            $schema = Schema::person()
                ->name($this->value('person_name'))
                ->url($this->model->site()->absoluteUrl());
        }

        return $schema->toScript();
    }

    protected function entrySchema(): ?string
    {
        $data = $this->value('json_ld')?->value();

        return $data
            ? '<script type="application/ld+json">'.$data.'</script>'
            : null;
    }

    protected function breadcrumbs(): ?string
    {
        // Don't render breadcrumbs if deactivated in the site defaults.
        if (! $this->value('use_breadcrumbs')) {
            return null;
        }

        // Don't render breadcrumbs on the homepage.
        if ($this->model->absoluteUrl() === $this->model->site()->absoluteUrl()) {
            return null;
        }

        $listItems = $this->breadcrumbsListItems()->map(function ($crumb) {
            return Schema::listItem()
                ->position($crumb['position'])
                ->name($crumb['title'])
                ->item($crumb['url']);
        })->all();

        return Schema::breadcrumbList()->itemListElement($listItems);
    }

    protected function breadcrumbsListItems(): Collection
    {
        $url = parse_url($this->model->absoluteUrl(), PHP_URL_PATH);
        $segments = collect(explode('/', $url))->filter()->prepend('/');

        $crumbs = $segments->map(function () use (&$segments) {
            $uri = URL::tidy($segments->join('/'));
            $segments->pop();

            return Data::findByUri(Str::ensureLeft($uri, '/'), $this->model->site()->handle());
        })
        ->filter()
        ->reverse()
        ->values()
        ->map(fn ($model, $key) => [
            'position' => $key + 1,
            'title' => method_exists($model, 'title') ? $model->title() : $model->value('title'),
            'url' => $model->absoluteUrl(),
        ]);

        return $crumbs;
    }
}
