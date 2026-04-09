<?php

namespace Aerni\AdvancedSeo\Cascades;

use Aerni\AdvancedSeo\Actions\ResolveBreadcrumbs;
use Aerni\AdvancedSeo\Concerns\EvaluatesIndexability;
use Aerni\AdvancedSeo\Concerns\HasComputedData;
use Aerni\AdvancedSeo\Concerns\HasHreflang;
use Aerni\AdvancedSeo\Facades\SocialImage;
use Aerni\AdvancedSeo\Support\Helpers;
use Illuminate\Support\Collection;
use Spatie\SchemaOrg\Organization;
use Spatie\SchemaOrg\Person;
use Spatie\SchemaOrg\Schema;
use Statamic\Facades\Site;
use Statamic\Support\Str;

class ContentCascade extends BaseCascade
{
    use EvaluatesIndexability;
    use HasComputedData;
    use HasHreflang;

    protected function process(): self
    {
        return $this
            ->withSiteDefaults()
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
            'og_title',
            'og_image_preset',
            'twitter_image_preset',
            'twitter_handle',
            'indexing',
            'locale',
            'hreflang',
            'canonical',
            'og_url',
            'site_schema',
            'page_schema',
            'breadcrumbs',
        ]);
    }

    public function siteName(): string
    {
        return $this->get('site_name');
    }

    public function title(): string
    {
        return $this->get('title') ?? $this->model->title() ?? $this->siteName();
    }

    public function ogTitle(): string
    {
        return $this->get('og_title') ?? $this->title();
    }

    public function ogUrl(): ?string
    {
        return $this->model->absoluteUrl();
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
        $indexing = collect([
            'noindex' => $this->get('noindex') || ! $this->crawlingIsEnabled(),
            'nofollow' => $this->get('nofollow') || ! $this->crawlingIsEnabled(),
        ])->filter()->keys()->implode(', ');

        return $indexing ?: null;
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

        return match ($this->get('canonical_type')) {
            'other' => $this->get('canonical_entry')?->absoluteUrl(),
            'custom' => $this->get('canonical_custom'),
            default => $this->canonicalUrl(),
        };
    }

    /**
     * Returns the default canonical URL for this model.
     * Overridden by ContextViewCascade to use Context::get('current_url').
     */
    protected function canonicalUrl(): string
    {
        return $this->model->absoluteUrl();
    }

    public function siteSchema(): ?string
    {
        $type = $this->get('site_json_ld_type');

        if (! $type || $type === 'none') {
            return null;
        }

        if ($type === 'custom') {
            return $this->get('site_json_ld');
        }

        $siteUrl = $this->siteUrl();

        $schema = match ($type) {
            'organization' => $this->organizationSchema($siteUrl),
            'person' => $this->personSchema($siteUrl),
            default => null,
        };

        return $schema ? json_encode($schema->toArray(), JSON_UNESCAPED_UNICODE) : null;
    }

    protected function organizationSchema(string $siteUrl): Organization
    {
        $schema = Schema::organization()
            ->name($this->get('organization_name'))
            ->url($siteUrl);

        if ($logo = $this->get('organization_logo')) {
            $schema->logo(
                Schema::imageObject()
                    ->url($logo->absoluteUrl())
                    ->width($logo->width())
                    ->height($logo->height())
            );
        }

        return $schema;
    }

    protected function personSchema(string $siteUrl): Person
    {
        return Schema::person()
            ->name($this->get('person_name'))
            ->url($siteUrl);
    }

    protected function siteUrl(): string
    {
        return $this->model->site()->absoluteUrl();
    }

    protected function siteHandle(): string
    {
        return $this->model->site()->handle();
    }

    protected function isHomepage(): bool
    {
        return $this->model->absoluteUrl() === $this->siteUrl();
    }

    protected function breadcrumbSegments(): array
    {
        return explode('/', trim($this->model->url(), '/'));
    }

    public function pageSchema(): ?string
    {
        return $this->get('json_ld')?->value();
    }

    public function breadcrumbs(): ?string
    {
        if (! $this->get('use_breadcrumbs')) {
            return null;
        }

        if ($this->isHomepage()) {
            return null;
        }

        $segments = array_merge(['/'], $this->breadcrumbSegments());

        $listItems = ResolveBreadcrumbs::handle($segments, $this->siteHandle())
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
