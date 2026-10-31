<?php

namespace Aerni\AdvancedSeo\Redirects;

use Aerni\AdvancedSeo\Concerns\ResolvesTermUrls;
use Aerni\AdvancedSeo\Facades\Seo;
use Aerni\AdvancedSeo\Features\Redirects as RedirectsFeature;
use Illuminate\Support\Collection;
use Statamic\Contracts\Entries\Collection as StatamicCollection;
use Statamic\Contracts\Taxonomies\Taxonomy;
use Statamic\Contracts\Taxonomies\Term;
use Statamic\Facades\Collection as CollectionFacade;
use Statamic\Facades\Term as TermFacade;
use Statamic\Facades\URL;
use Statamic\Facades\YAML;
use Statamic\Taxonomies\LocalizedTerm;

class AutomaticTermRedirects
{
    use ResolvesTermUrls;

    /** @var array<string, array<string, string|null>> */
    private array $terms = [];

    /** @var array<string, list<array{term: string, site: string, collection: string, path: string}>> */
    private array $affectedCollectionTerms = [];

    /** @var array<string, list<array{site: string, path: string}>> */
    private array $deleting = [];

    public function __construct(private AutomaticRedirectWriter $redirects) {}

    /**
     * Stash localization slug overrides that differ from the term now.
     * A null slug means that site inherited the base slug.
     */
    public function stash(Term $term): void
    {
        if (! $this->isRedirectable($term)) {
            return;
        }

        if (! $original = $this->originalLocalizationSlugs($term)) {
            return;
        }

        $changed = [];

        foreach ($original as $site => $slug) {
            if ($term->in($site)->get('slug') === $slug) {
                continue;
            }

            $changed[$site] = $slug;
        }

        if ($changed === []) {
            return;
        }

        $this->terms[$term->id()] = $changed;
    }

    /**
     * The base slug is still on getOriginal() at TermSaved. Localization
     * overrides were stashed on TermSaving. Terms carry no date and can't be
     * an entry:: destination, so the redirect points at the new path.
     *
     * A base-slug change flows to every localization that inherits it, so a
     * redirect is created per site. A localization whose own slug changed is
     * redirected on its own. An unchanged override drops out (old URL equals
     * new URL). A path is included when Statamic would serve that page. The
     * canonical URL needs the term view, checked per localization because a
     * site can set its own template. A collection adds its path when its
     * scoped view exists.
     *
     * TODO: Statamic PR #15192 (draft) adds configurable term routes. A term
     * URL is only the slug today, so this only watches slug changes. Once
     * that ships, a custom route can move the URL without a slug change, a
     * disabled route has no URL, and a custom route no longer gains the
     * collection prefix. Handle those the way entry routes are handled.
     */
    public function create(Term $term): void
    {
        if (! $this->isRedirectable($term)) {
            return;
        }

        $currentPaths = $this->termSitePaths($term);

        // A term owns its URL in every site, so delete any automatic redirect that uses it.
        foreach ($currentPaths as $current) {
            $this->redirects->deleteBySource($current['path'], $current['site']);
        }

        $originalSlug = $term->getOriginal('slug');
        $newSlug = $term->slug();
        $originalSlugs = $this->terms[$term->id()] ?? [];

        unset($this->terms[$term->id()]);

        $baseSlugChanged = filled($originalSlug) && $originalSlug !== $newSlug;

        // Skip new terms and saves that move no URL.
        if (! $baseSlugChanged && $originalSlugs === []) {
            return;
        }

        if ($baseSlugChanged) {
            $term->slug($originalSlug);
        }

        $currentSlugs = [];

        foreach ($originalSlugs as $site => $slug) {
            $currentSlugs[$site] = $term->in($site)->get('slug');
            $term->in($site)->set('slug', $slug);
        }

        $originalPaths = $this->termSitePaths($term);

        if ($baseSlugChanged) {
            $term->slug($newSlug);
        }

        foreach ($currentSlugs as $site => $slug) {
            $term->in($site)->set('slug', $slug);
        }

        foreach ($currentPaths as $key => $current) {
            $original = $originalPaths[$key] ?? null;

            if (! $original || $original['path'] === $current['path']) {
                continue;
            }

            $this->redirects->create($original['path'], $current['path'], $current['site']);
        }
    }

    /**
     * A deleted term leaves the auto-created redirects that pointed at it with
     * a dead destination, so remove them. Those redirects point at a path
     * rather than an entry reference, so match on the term's URL per site.
     * Cleanup still applies when the term's view no longer exists.
     * Manual redirects and redirects whose source is the now-gone URL are left alone.
     */
    public function delete(Term $term): void
    {
        if (! RedirectsFeature::enabled()) {
            return;
        }

        foreach ($this->termSitePaths($term, onlyPublicUrls: false) as $current) {
            $this->redirects->deleteByDestination($current['path'], $current['site']);
        }
    }

    public function stashAffected(AffectedUrls $change): void
    {
        foreach ($change->mounts as $mount) {
            $this->affectedCollectionTerms[$change->key] = collect($this->affectedCollectionTerms[$change->key] ?? [])
                ->concat($mount['collections']->flatMap(fn (StatamicCollection $collection) => $this->affectedCollectionTermPaths($mount['site'], $collection, $mount['path'])))
                ->keyBy(fn (array $term) => "{$term['site']}|{$term['collection']}|{$term['term']}")
                ->values()
                ->all();
        }
    }

    public function createAffected(AffectedUrls $change): void
    {
        $key = $change->key;
        $terms = $this->affectedCollectionTerms[$key] ?? [];
        unset($this->affectedCollectionTerms[$key]);

        if (! RedirectsFeature::enabled()) {
            return;
        }

        foreach ($terms as $term) {
            $this->redirectAffectedCollectionTerm($term);
        }
    }

    /** @param Collection<int, Taxonomy> $scopes */
    public function stashDeletion(string $key, Collection $scopes, ?string $site = null): void
    {
        if (! RedirectsFeature::enabled()) {
            return;
        }

        $paths = [];

        foreach ($scopes as $scope) {
            $terms = (clone $scope)->collection(null)->queryTerms()->get();

            foreach ($site === null ? $scope->sites() : [$site] as $locale) {
                foreach ($terms as $term) {
                    $localized = $term->in($locale);

                    if ($localized && ($path = (clone $localized)->collection($scope->collection())->uri())) {
                        $paths[] = ['site' => $locale, 'path' => $path];
                    }
                }
            }
        }

        $this->deleting[$key] = collect($paths)->unique(fn (array $path) => "{$path['site']}|{$path['path']}")->values()->all();
    }

    public function deleteAffected(string $key): void
    {
        $paths = $this->deleting[$key] ?? [];
        unset($this->deleting[$key]);

        if (! RedirectsFeature::enabled()) {
            return;
        }

        foreach ($paths as $path) {
            if (! $this->redirects->hasPublicOwner($path['path'], $path['site'])) {
                $this->redirects->deleteByDestination($path['path'], $path['site']);
            }
        }
    }

    /**
     * Redirects are enabled and this term is published.
     */
    private function isRedirectable(Term $term): bool
    {
        if (! RedirectsFeature::enabled()) {
            return false;
        }

        if (! Seo::find("taxonomies::{$term->taxonomyHandle()}")?->config()->value('redirects')) {
            return false;
        }

        // Terms are always published.
        return $term->status() === 'published';
    }

    /**
     * Localization slug overrides from before this save, keyed by site.
     * Null when the term has never been saved. A null slug means that site
     * inherited the base slug.
     *
     * @return array<string, string|null>|null
     */
    private function originalLocalizationSlugs(Term $term): ?array
    {
        $data = null;

        // Flat file: the previous overrides are still in the file.
        if ($path = $term->initialPath()) {
            $data = YAML::file($path)->parse();
        }

        // No file and no previous slug: the term has never been saved.
        if ($data === null && ! filled($term->getOriginal('slug'))) {
            return null;
        }

        // Eloquent: there is no file, so the model is still the pre-save row.
        if ($data === null && method_exists($term, 'model')) {
            $data = $term->model()->getAttribute('data');
        }

        if (! is_array($data)) {
            return null;
        }

        $localizations = $data['localizations'] ?? [];
        $slugs = [];

        foreach ($term->taxonomy()->sites() as $site) {
            if ($site === $term->defaultLocale()) {
                continue;
            }

            $slugs[$site] = $localizations[$site]['slug'] ?? null;
        }

        return $slugs;
    }

    /**
     * Site and path pairs for every localization of the term, keyed by site and
     * collection so the old and new paths match. A site's canonical path comes
     * before its collection paths. Creation includes only public paths; deletion
     * also includes paths whose views no longer exist.
     *
     * @return array<string, array{site: string, collection: ?string, path: string}>
     */
    private function termSitePaths(Term $term, bool $onlyPublicUrls = true): array
    {
        return $term->localizations()
            ->flatMap(fn (LocalizedTerm $localized) => $this->termPathsForSite($localized, $onlyPublicUrls))
            ->keyBy(fn (array $path) => $path['collection']
                ? "{$path['site']}.{$path['collection']}"
                : $path['site'])
            ->all();
    }

    /**
     * @param  array{term: string, site: string, collection: string, path: string}  $stashed
     */
    private function redirectAffectedCollectionTerm(array $stashed): void
    {
        $localization = TermFacade::find($stashed['term'])->in($stashed['site']);
        $collection = CollectionFacade::findByHandle($stashed['collection']);

        $currentPath = $this->collectionTermPath($localization, $collection);

        if (! $currentPath || $currentPath === $stashed['path']) {
            return;
        }

        $this->redirects->deleteBySource($currentPath, $stashed['site']);
        $this->redirects->create($stashed['path'], $currentPath, $stashed['site']);
    }

    /**
     * Old collection term paths. The mount entry already resolves to the new
     * URL, so the old path is built from the URI still stored for it.
     *
     * @return Collection<int, array{term: string, site: string, collection: string, path: string}>
     */
    private function affectedCollectionTermPaths(string $site, StatamicCollection $collection, ?string $mountPath): Collection
    {
        if (! $mountPath) {
            return collect();
        }

        return $collection->taxonomies()
            ->filter(fn (Taxonomy $taxonomy) => $this->redirectsEnabled($taxonomy))
            ->flatMap(function (Taxonomy $taxonomy) use ($collection, $site, $mountPath) {
                $suffix = '/'.str_replace('_', '-', $taxonomy->handle());

                return $taxonomy->queryTerms()->get()
                    ->map(fn ($term) => $term->in($site))
                    ->filter(fn (?LocalizedTerm $localized) => $localized && $this->collectionTermPath($localized, $collection) !== null)
                    ->map(fn (LocalizedTerm $localized) => [
                        'term' => $localized->id(),
                        'site' => $site,
                        'collection' => $collection->handle(),
                        'path' => URL::tidy(url: $mountPath.$suffix.'/'.$localized->slug(), withTrailingSlash: false),
                    ]);
            });
    }

    private function redirectsEnabled(Taxonomy $taxonomy): bool
    {
        return (bool) Seo::find("taxonomies::{$taxonomy->handle()}")?->config()->value('redirects');
    }
}
