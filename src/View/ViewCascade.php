<?php

namespace Aerni\AdvancedSeo\View;

use Aerni\AdvancedSeo\Actions\EvaluateContextType;
use Aerni\AdvancedSeo\Concerns\WithComputedData;
use Aerni\AdvancedSeo\Facades\SocialImage;
use Aerni\AdvancedSeo\Models\Defaults;
use Aerni\AdvancedSeo\Support\Helpers;
use Illuminate\Support\Collection;
use Spatie\SchemaOrg\Schema;
use Statamic\Contracts\Entries\Entry;
use Statamic\Facades\Blink;
use Statamic\Facades\Data;
use Statamic\Facades\Site;
use Statamic\Facades\URL;
use Statamic\Fields\Value;
use Statamic\Stache\Query\TermQueryBuilder;
use Statamic\Support\Str;
use Statamic\Tags\Context;

class ViewCascade extends BaseCascade
{
    use WithComputedData;

    public function __construct(Context $model)
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

    public function processComputedData(): self
    {
        $this->data = $this->data->merge([
            'title' => $this->compiledTitle(),
            'og_image' => $this->ogImage(),
            'og_image_preset' => $this->ogImagePreset(),
            'og_title' => $this->ogTitle(),
            'twitter_card' => $this->twitterCard(),
            'twitter_image' => $this->twitterImage(),
            'twitter_image_preset' => $this->twitterImagePreset(),
            'twitter_title' => $this->twitterTitle(),
            'twitter_handle' => $this->twitterHandle(),
            'indexing' => $this->indexing(),
            'locale' => $this->locale(),
            'hreflang' => $this->hreflang(),
            'canonical' => $this->canonical(),
            'prev_url' => $this->prevUrl(),
            'next_url' => $this->nextUrl(),
            'schema' => $this->schema(),
            'breadcrumbs' => $this->breadcrumbs(),
        ]);

        return $this;
    }

    protected function isType(string $type): bool
    {
        return EvaluateContextType::handle($this->model) === $type;
    }

    protected function compiledTitle(): string
    {
        $position = $this->value('site_name_position')?->value();

        return match (true) {
            ($position === 'end') => "{$this->title()} {$this->titleSeparator()} {$this->siteName()}",
            ($position === 'start') => "{$this->siteName()} {$this->titleSeparator()} {$this->title()}",
            ($position === 'disabled') => $this->title(),
            default => "{$this->title()} {$this->titleSeparator()} {$this->siteName()}",
        };
    }

    protected function title(): string
    {
        return match (true) {
            $this->isType('taxonomy') => $this->model->get('title'),
            $this->isType('error') => $this->model->get('response_code'),
            default => $this->get('title'),
        };
    }

    protected function titleSeparator(): string
    {
        return $this->get('title_separator');
    }

    protected function siteName(): string
    {
        return $this->get('site_name') ?? config('app.name');
    }

    protected function ogTitle(): string
    {
        return $this->get('og_title') ?? $this->title();
    }

    protected function ogImage(): ?Value
    {
        return $this->value('generate_social_images')
            ? $this->get('generated_og_image')
            : $this->get('og_image');
    }

    protected function ogImagePreset(): ?array
    {
        if (! $this->ogImage()) {
            return null;
        }

        return collect(SocialImage::findModel('open_graph'))
            ->only(['width', 'height'])
            ->all();
    }

    protected function twitterTitle(): string
    {
        return $this->get('twitter_title') ?? $this->title();
    }

    protected function twitterCard(): string
    {
        if ($card = $this->get('twitter_card')) {
            return $card;
        }

        /**
         * Determine the twitter card based on the images set in the social media defaults.
         * This is used on taxonomy and error pages.
         */
        $image = $this->get('twitter_summary_large_image') ?? $this->get('twitter_summary_image');

        return $image?->field()?->config()['twitter_card'] ?? Defaults::data('collections')->get('seo_twitter_card');
    }

    protected function twitterImage(): ?Value
    {
        if (! $model = SocialImage::findModel("twitter_{$this->twitterCard()}")) {
            return null;
        }

        return $this->value('generate_social_images')
            ? $this->get('generated_twitter_image')
            : $this->get($model['handle']);
    }

    protected function twitterImagePreset(): ?array
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
        return Helpers::parseLocale(Site::current()->locale());
    }

    protected function hreflang(): ?array
    {
        // Handles collection taxonomy index page.
        if ($this->model->has('segment_2') && $this->model->get('terms') instanceof TermQueryBuilder) {
            $taxonomy = $this->model->get('title')->augmentable();

            return $taxonomy->sites()->map(function ($site) use ($taxonomy) {
                $site = Site::get($site);
                $siteUrl = $site->absoluteUrl();
                $taxonomyHandle = $taxonomy->handle();
                $collectionHandle = $taxonomy->collection()->handle();

                return [
                    'url' => URL::tidy("{$siteUrl}/{$collectionHandle}/{$taxonomyHandle}"),
                    'locale' => Helpers::parseLocale($site->locale()),
                ];
            })->all();
        }

        // Handles collection taxonomy show page.
        if ($this->model->has('segment_3') && $this->model->value('is_term') === true) {
            $localizedTerm = $this->model->get('title')->augmentable();

            return $localizedTerm->taxonomy()->sites()
                ->map(fn ($locale) => [
                    'url' => $localizedTerm->in($locale)->absoluteUrl(),
                    'locale' => Helpers::parseLocale(Site::get($locale)->locale()),
                ])->all();
        }

        // Handles taxonomy index page.
        if ($this->model->has('segment_1') && $this->model->get('terms') instanceof TermQueryBuilder) {
            $taxonomy = $this->model->get('terms')->first()->taxonomy();

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

        // Handle entries and term show page.
        $data = Data::find($this->model->get('id'));

        if (! $data) {
            return null;
        }

        $sites = $data instanceof Entry
            ? $data->sites()
            : $data->taxonomy()->sites();

        $hreflang = $sites->map(fn ($locale) => $data->in($locale))
            ->filter() // A model might no exist in a site. So we need to remove it to prevent further issues.
            ->filter(fn ($model) => $model->published()) // Remove any unpublished entries/terms
            ->filter(fn ($model) => $model->url()) // Remove any entries/terms with no route
            ->map(fn ($model) => [
                'url' => $model->absoluteUrl(),
                'locale' => Helpers::parseLocale($model->site()->locale()),
            ])->all();

        return $hreflang;
    }

    protected function canonical(): string
    {
        $type = $this->value('canonical_type');

        if ($type == 'other' && $this->value('canonical_entry')) {
            return $this->value('canonical_entry')->absoluteUrl();
        }

        if ($type == 'custom' && $this->value('canonical_custom')) {
            return $this->value('canonical_custom');
        }

        $currentUrl = $this->model->get('current_url');

        // Don't add the pagination parameter if it doesn't exists or there's no paginator on the page.
        if (! app('request')->has('page') || ! Blink::get('tag-paginator')) {
            return $currentUrl;
        }

        $page = (int) app('request')->get('page');

        // Don't include the pagination parameter for the first page. We don't want the same site to be indexed with and without parameter.
        return $page === 1
            ? $currentUrl
            : "{$currentUrl}?page={$page}";
    }

    protected function prevUrl(): ?string
    {
        if (! $paginator = Blink::get('tag-paginator')) {
            return null;
        }

        $currentUrl = $this->model->get('current_url');

        $page = $paginator->currentPage();

        // Don't include the pagination parameter for the first page. We don't want the same site to be indexed with and without parameter.
        if ($page === 2) {
            return $currentUrl;
        }

        return $page > 1 && $page <= $paginator->lastPage()
            ? $currentUrl.'?page='.($page - 1)
            : null;
    }

    protected function nextUrl(): ?string
    {
        if (! $paginator = Blink::get('tag-paginator')) {
            return null;
        }

        $currentUrl = $this->model->get('current_url');

        $page = $paginator->currentPage();

        return $page < $paginator->lastPage()
            ? $currentUrl.'?page='.($page + 1)
            : null;
    }

    protected function schema(): ?string
    {
        $schema = $this->siteSchema().$this->entrySchema();

        return ! empty($schema) ? $schema : null;
    }

    protected function siteSchema(): ?string
    {
        $type = $this->value('site_json_ld_type')?->value();

        if ($type === 'none') {
            return null;
        }

        if ($type === 'custom') {
            $data = $this->value('site_json_ld')?->value();

            return $data
                ? '<script type="application/ld+json">'.$data.'</script>'
                : null;
        }

        if ($type === 'organization') {
            $schema = Schema::organization()
                ->name($this->value('organization_name'))
                ->url($this->model->get('site')->absoluteUrl());

            if ($logo = $this->value('organization_logo')) {
                $logo = Schema::imageObject()
                    ->url($logo->absoluteUrl())
                    ->width($logo->width())
                    ->height($logo->height());

                $schema->logo($logo);
            }
        }

        if ($type === 'person') {
            $schema = Schema::person()
                ->name($this->value('person_name'))
                ->url($this->model->get('site')->absoluteUrl());
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
        if ($this->model->get('is_homepage')) {
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
        $segments = collect(request()->segments())->prepend('/');

        $crumbs = $segments->map(function () use (&$segments) {
            $uri = URL::tidy($segments->join('/'));
            $segments->pop();

            return Data::findByUri(Str::ensureLeft($uri, '/'), Site::current()->handle());
        })
        ->filter()
        ->reverse()
        ->values()
        ->map(function ($item, $key) {
            return [
                'position' => $key + 1,
                'title' => method_exists($item, 'title') ? $item->title() : $item->value('title'),
                'url' => $item->absoluteUrl(),
            ];
        });

        return $crumbs;
    }
}
