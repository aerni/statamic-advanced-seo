<?php

namespace Aerni\AdvancedSeo\View;

use Aerni\AdvancedSeo\Actions\ResolveBreadcrumbs;
use Aerni\AdvancedSeo\Concerns\EvaluatesIndexability;
use Aerni\AdvancedSeo\Data\HasComputedData;
use Aerni\AdvancedSeo\Facades\SocialImage;
use Aerni\AdvancedSeo\Support\Helpers;
use Aerni\AdvancedSeo\View\Concerns\HasHreflang;
use Illuminate\Support\Collection;
use Spatie\SchemaOrg\Schema;
use Statamic\Contracts\Entries\Entry;
use Statamic\Contracts\Taxonomies\Term;
use Statamic\Facades\Site;
use Statamic\Support\Str;

class GraphQlCascade extends BaseCascade
{
    use EvaluatesIndexability;
    use HasComputedData;
    use HasHreflang;

    public function __construct(Entry|Term $model)
    {
        parent::__construct($model);
    }

    protected function process(): self
    {
        return $this
            ->withSiteDefaults()
            ->withContentConfig()
            ->withPageData()
            ->removeSeoPrefix()
            ->ensureOverrides()
            ->sortKeys();
    }

    public function computedKeys(): Collection
    {
        return collect([
            'site_name',
            'title',
            'og_image_preset',
            'og_title',
            'twitter_card',
            'twitter_image_preset',
            'twitter_handle',
            'indexing',
            'locale',
            'hreflang',
            'canonical',
            'site_schema',
            'breadcrumbs',
        ]);
    }

    protected function pageTitle(): string
    {
        return $this->get('title') ?? $this->model->get('title');
    }

    public function siteName(): string
    {
        return $this->get('site_name');
    }

    public function title(): string
    {
        $siteNamePosition = $this->get('site_name_position');
        $titleSeparator = $this->get('title_separator');
        $siteName = $this->siteName();
        $pageTitle = $this->pageTitle();

        return match (true) {
            (! $pageTitle) => $siteName,
            ($siteNamePosition == 'end') => "{$pageTitle} {$titleSeparator} {$siteName}",
            ($siteNamePosition == 'start') => "{$siteName} {$titleSeparator} {$pageTitle}",
            ($siteNamePosition == 'disabled') => $pageTitle,
            default => "{$pageTitle} {$titleSeparator} {$siteName}",
        };
    }

    public function ogTitle(): string
    {
        return $this->get('og_title') ?? $this->pageTitle() ?? $this->siteName();
    }

    public function ogImagePreset(): array
    {
        $openGraph = SocialImage::openGraph();

        return [
            'width' => $openGraph->width(),
            'height' => $openGraph->height(),
        ];
    }

    public function twitterImagePreset(): array
    {
        return config("advanced-seo.social_images.presets.twitter_{$this->get('twitter_card')}");
    }

    public function twitterHandle(): ?string
    {
        $twitterHandle = $this->get('twitter_handle');

        return $twitterHandle ? Str::start($twitterHandle, '@') : null;
    }

    public function indexing(): ?string
    {
        // TODO: Could use crawlingIsEnabled() method instead.
        if (! in_array(app()->environment(), config('advanced-seo.crawling.environments', []))) {
            $this->merge(['noindex' => true, 'nofollow' => true]);
        }

        $indexing = collect([
            'noindex' => $this->get('noindex'),
            'nofollow' => $this->get('nofollow'),
        ])->filter()->keys()->implode(', ');

        return ! empty($indexing) ? $indexing : null;
    }

    public function locale(): string
    {
        return Helpers::parseLocale($this->model->site()->locale());
    }

    public function hreflang(): ?array
    {
        if (! Site::multiEnabled()) {
            return null;
        }

        return $this->entryAndTermHreflang($this->model);
    }

    public function canonical(): ?string
    {
        if (! $this->isIndexable($this->model)) {
            return null;
        }

        $type = $this->get('canonical_type');

        if ($type == 'other' && $this->has('canonical_entry')) {
            return $this->get('canonical_entry')->absoluteUrl();
        }

        if ($type == 'custom' && $this->has('canonical_custom')) {
            return $this->get('canonical_custom');
        }

        return $this->model->absoluteUrl();
    }

    public function siteSchema(): ?string
    {
        $type = $this->get('site_json_ld_type');

        if ($type == 'none') {
            return null;
        }

        if ($type == 'custom') {
            return $this->get('site_json_ld');
        }

        if ($type == 'organization') {
            $schema = Schema::organization()
                ->name($this->get('organization_name'))
                ->url($this->model->site()->absoluteUrl());

            if ($logo = $this->get('organization_logo')) {
                $logo = Schema::imageObject()
                    ->url($logo->absoluteUrl())
                    ->width($logo->width())
                    ->height($logo->height());

                $schema->logo($logo);
            }
        }

        if ($type == 'person') {
            $schema = Schema::person()
                ->name($this->get('person_name'))
                ->url($this->model->site()->absoluteUrl());
        }

        return json_encode($schema->toArray(), JSON_UNESCAPED_UNICODE);
    }

    public function breadcrumbs(): ?string
    {
        // Don't render breadcrumbs if deactivated in the site defaults.
        if (! $this->get('use_breadcrumbs')) {
            return null;
        }

        // Don't render breadcrumbs on the homepage.
        if ($this->model->absoluteUrl() === $this->model->site()->absoluteUrl()) {
            return null;
        }

        $segments = array_merge(['/'], explode('/', trim($this->model->url(), '/')));

        $listItems = ResolveBreadcrumbs::handle($segments, $this->model->site()->handle())
            ->map(fn ($crumb) => Schema::listItem()
                ->position($crumb['position'])
                ->name($crumb['title'])
                ->item($crumb['url']));

        return json_encode(
            Schema::breadcrumbList()->itemListElement($listItems),
            JSON_UNESCAPED_UNICODE,
        );
    }
}
