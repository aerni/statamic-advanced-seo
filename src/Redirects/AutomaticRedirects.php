<?php

namespace Aerni\AdvancedSeo\Redirects;

use Aerni\AdvancedSeo\Contracts\Redirect;
use Aerni\AdvancedSeo\Enums\RedirectOrigin;
use Aerni\AdvancedSeo\Enums\RedirectResponseCode;
use Aerni\AdvancedSeo\Facades\Redirect as RedirectFacade;
use Aerni\AdvancedSeo\Facades\Seo;
use Aerni\AdvancedSeo\Features\Redirects as RedirectsFeature;
use Carbon\Carbon;
use Statamic\Contracts\Entries\Entry;
use Statamic\Contracts\Taxonomies\Term;
use Statamic\Entries\GetDateFromPath;
use Statamic\Entries\GetSlugFromPath;
use Statamic\Events\CollectionTreeEntriesMovedOrRemoved;
use Statamic\Events\CollectionTreeSaved;
use Statamic\Events\EntryDeleted;
use Statamic\Events\EntrySaved;
use Statamic\Events\EntrySaving;
use Statamic\Events\TermDeleted;
use Statamic\Events\TermSaved;
use Statamic\Events\TermSaving;
use Statamic\Facades\Antlers;
use Statamic\Facades\Blink;
use Statamic\Facades\Entry as EntryFacade;
use Statamic\Facades\Stache;
use Statamic\Facades\Term as TermFacade;
use Statamic\Facades\YAML;
use Statamic\Structures\CollectionTree;

class AutomaticRedirects
{
    /** @var array<string, string> */
    private array $entries = [];

    /**
     * Stored paths of structure child pages, keyed by the parent entry id.
     *
     * @var array<string, array<string, string>>
     */
    private array $childPages = [];

    /**
     * Stored paths of moved entries, keyed by collection and site.
     *
     * @var array<string, array<string, string>>
     */
    private array $movedEntries = [];

    /** @var array<string, array<string, string|null>> */
    private array $terms = [];

    public function stash(EntrySaving|TermSaving|CollectionTreeEntriesMovedOrRemoved $event): void
    {
        if ($event instanceof EntrySaving) {
            $this->stashEntry($event->entry);
            $this->stashChildPages($event->entry);
            $this->stashEntryLocalizations($event->entry);
        }

        if ($event instanceof TermSaving) {
            $this->stashTerm($event->term);
        }

        if ($event instanceof CollectionTreeEntriesMovedOrRemoved) {
            $this->stashMovedEntries($event->moved);
        }
    }

    public function create(EntrySaved|TermSaved|CollectionTreeSaved $event): void
    {
        if ($event instanceof EntrySaved) {
            $this->redirectEntry($event->entry);
            $this->redirectChildPages($event->entry);
        }

        if ($event instanceof TermSaved) {
            $this->redirectTerm($event->term);
        }

        if ($event instanceof CollectionTreeSaved) {
            $this->redirectMovedEntries($event->tree);
        }
    }

    public function delete(EntryDeleted|TermDeleted $event): void
    {
        match (true) {
            $event instanceof EntryDeleted => $this->deleteEntry($event->entry),
            $event instanceof TermDeleted => $this->deleteTerm($event->term),
        };
    }

    /**
     * Redirects are enabled and this item is published. Child pages and
     * localizations are checked on their own, so a false result here does
     * not stop the rest of the save.
     */
    private function isRedirectable(Entry|Term $item): bool
    {
        if (! RedirectsFeature::enabled()) {
            return false;
        }

        $handle = match (true) {
            $item instanceof Entry => "collections::{$item->collectionHandle()}",
            $item instanceof Term => "taxonomies::{$item->taxonomyHandle()}",
        };

        if (! Seo::find($handle)?->config()->value('redirects')) {
            return false;
        }

        // Only a live item owns its URL; draft, scheduled and expired entries
        // aren't publicly reachable. Terms are always published.
        return $item->status() === 'published';
    }

    /**
     * Stash this entry's original URL when its slug, date, or a route field
     * changed, it is live now, and it was live before this save.
     */
    private function stashEntry(Entry $entry): void
    {
        if (! $this->isRedirectable($entry)) {
            return;
        }

        if (! $this->wasLive($entry)) {
            return;
        }

        $changed = $this->changedValues($entry);

        if ($changed->isEmpty()) {
            return;
        }

        if (! $originalUrl = $this->originalUrl($entry, $changed)) {
            return;
        }

        $this->entries[$entry->id()] = $entry->site()->relativePath($originalUrl);
    }

    /**
     * The entry was publicly reachable before this save. The status on the
     * entry itself is already the new one.
     */
    private function wasLive(Entry $entry): bool
    {
        $path = $entry->initialPath();

        // Flat file: the previous published flag is still in the file, and the
        // date is still in the filename. getOriginal() has already been replaced.
        if ($path) {
            $published = YAML::file($path)->parse()['published'] ?? true;
            $date = $this->parseDate((new GetDateFromPath)($path));
        }

        // Eloquent: there is no file, so getOriginal() is still the pre-save values.
        if (! $path) {
            $published = $entry->getOriginal('published');
            $date = $this->parseDate($entry->getOriginal('date'));
        }

        if (! $published) {
            return false;
        }

        $collection = $entry->collection();

        // A dated collection can hide a published entry when its date is private.
        if ($collection->dated() && $collection->futureDateBehavior() === 'private' && $date?->isFuture()) {
            return false;
        }

        if ($collection->dated() && $collection->pastDateBehavior() === 'private' && $date?->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Parse a stored date string into a Carbon at the precision it was stored.
     */
    private function parseDate(?string $date): ?Carbon
    {
        if (! $date) {
            return null;
        }

        $format = match (strlen($date)) {
            10 => 'Y-m-d',
            15 => 'Y-m-d-Hi',
            17 => 'Y-m-d-His',
        };

        $parsed = Carbon::createFromFormat($format, $date);

        return match ($format) {
            'Y-m-d' => $parsed->startOfDay(),
            'Y-m-d-Hi' => $parsed->startOfMinute(),
            'Y-m-d-His' => $parsed,
        };
    }

    /**
     * Previous values that differ from the entry now.
     * Route fields include tokens used by a localization route.
     * Empty when none of those values changed.
     */
    private function changedValues(Entry $entry): EntryUrlValues
    {
        $original = $this->originalValues($entry);
        $current = $this->currentValues($entry, $original);

        return $original->diff($current);
    }

    /**
     * Slug, date, and entry data from before this save.
     * Empty when the entry has never been saved.
     */
    private function originalValues(Entry $entry): EntryUrlValues
    {
        // Flat file: the previous slug and date are still in the filename.
        // getOriginal() has already been replaced with the new values.
        if ($path = $entry->initialPath()) {
            return new EntryUrlValues(
                slug: (new GetSlugFromPath)($path),
                date: (new GetDateFromPath)($path),
                data: $this->routeFieldValues($entry),
            );
        }

        // Eloquent: there is no file, so getOriginal() is still the pre-save slug, date, and data.
        if ($slug = $entry->getOriginal('slug')) {
            return new EntryUrlValues(
                slug: $slug,
                date: $entry->getOriginal('date'),
                data: $this->routeFieldValues($entry),
            );
        }

        // No file and no previous slug: the entry has never been saved.
        return new EntryUrlValues;
    }

    /**
     * Route-field values from the file, or from getOriginal() when there is no file.
     * Tokens come from this entry's route and from localization routes, which
     * can use a field this route does not. Null when nothing uses a field
     * token, so the file is left unread.
     *
     * @return array<string, mixed>|null
     */
    private function routeFieldValues(Entry $entry): ?array
    {
        $tokens = collect([$entry])
            ->merge($entry->descendants())
            ->flatMap(fn (Entry $candidate) => $this->routeFieldTokens($candidate))
            ->unique()
            ->values();

        if ($tokens->isEmpty()) {
            return null;
        }

        // Flat file reads the file. Eloquent reads getOriginal().
        $source = ($path = $entry->initialPath())
            ? YAML::file($path)->parse()
            : $entry->getOriginal();

        return $tokens
            ->mapWithKeys(fn (string $token) => [$token => $source[$token] ?? null])
            ->all();
    }

    /**
     * Route tokens that read an entry field, ignoring slug, date, and the
     * structure tokens Statamic fills in itself.
     * Null when the route has none.
     *
     * @return array<int, string>|null
     */
    private function routeFieldTokens(Entry $entry): ?array
    {
        if (! $route = $entry->route()) {
            return null;
        }

        $route = preg_replace('/(?<!{){\s*([a-zA-Z0-9_\-]+)\s*}(?!})/', '{{ $1 }}', $route);

        $tokens = collect(Antlers::identifiers($route))
            ->reject(fn (string $token) => in_array($token, [
                'slug', 'date', 'year', 'month', 'day',
                'parent_uri', 'mount', 'depth', 'is_root', 'id', 'published',
            ]))
            ->values()
            ->all();

        return $tokens === [] ? null : $tokens;
    }

    /**
     * The entry's slug, date, and route-field data as they are now.
     * The date uses the original value's precision so the two can be compared.
     */
    private function currentValues(Entry $entry, EntryUrlValues $original): EntryUrlValues
    {
        $data = null;

        foreach (array_keys($original->data ?? []) as $token) {
            $data[$token] = $entry->get($token);
        }

        $date = $original->date ? $entry->date()?->format(match (strlen($original->date)) {
            15 => 'Y-m-d-Hi',
            17 => 'Y-m-d-His',
            default => 'Y-m-d',
        }) : null;

        return new EntryUrlValues(
            slug: $entry->slug(),
            date: $date,
            data: $data,
        );
    }

    /**
     * Let the routing compute the URL the entry had before this save by
     * temporarily restoring the previous slug, date, and route fields. The
     * cached uri has to be flushed for the swapped values to take effect.
     */
    private function originalUrl(Entry $entry, EntryUrlValues $values): ?string
    {
        $slug = $entry->slug();
        $date = $entry->date();
        $data = collect($values->data ?? [])
            ->map(fn (mixed $value, string $token) => $entry->get($token))
            ->all();

        if ($values->slug !== null) {
            $entry->slug($values->slug);
        }

        if ($values->date !== null) {
            $entry->date($values->date);
        }

        foreach ($values->data ?? [] as $token => $value) {
            $entry->set($token, $value);
        }

        Blink::store('entry-uris')->forget($entry->id());

        $url = $entry->absoluteUrlWithoutRedirect();

        if ($values->slug !== null) {
            $entry->slug($slug);
        }

        if ($values->date !== null) {
            $entry->date($date);
        }

        foreach ($data as $token => $value) {
            $entry->set($token, $value);
        }

        Blink::store('entry-uris')->forget($entry->id());

        return $url;
    }

    /**
     * A parent slug, date, or route field change moves every child page.
     * Stash the path still stored for each published one.
     */
    private function stashChildPages(Entry $entry): void
    {
        if (! $entry->page()) {
            return;
        }

        if ($this->changedValues($entry)->isEmpty()) {
            return;
        }

        $this->stashChildPagePaths($entry);
    }

    /**
     * Stash the stored path of each published child page.
     */
    private function stashChildPagePaths(Entry $entry): void
    {
        if (! $page = $entry->page()) {
            return;
        }

        $this->childPages[$entry->id()] = $this->stashOriginalPaths(
            $page->flattenedPages()->map->id()->all(),
        );
    }

    /**
     * An origin route field or date is inherited by localizations that do not
     * set it. A localization route may use a field the origin route does not.
     * Entry::descendants() is that origin chain. Stash each live
     * localization's stored path, and its published child pages, before this
     * save rewrites them.
     */
    private function stashEntryLocalizations(Entry $entry): void
    {
        $changed = $this->changedValues($entry);

        if (! $changed->data && ! $changed->date) {
            return;
        }

        foreach ($entry->descendants() as $localization) {
            if (! $this->inheritsChangedValues($entry, $localization, $changed)) {
                continue;
            }

            $this->entries += $this->stashOriginalPaths([$localization]);

            $this->stashChildPagePaths($localization);

            // Eloquent copies the origin date onto the localization. Clear that
            // copy so the save that follows writes the new date into the URI.
            // The old path was already read above.
            if ($changed->date && ! $localization->initialPath() && $localization->hasExplicitDate()) {
                $localization->date(null);
            }
        }
    }

    /**
     * True when a changed route field or date is absent on the localization,
     * so it uses the origin's value. A route field only counts when this
     * localization's route uses it.
     */
    private function inheritsChangedValues(Entry $origin, Entry $localization, EntryUrlValues $changed): bool
    {
        $inheritsDate = $changed->date && $this->inheritsDate($origin, $localization);

        $inheritsField = collect($changed->data)
            ->keys()
            ->intersect($this->routeFieldTokens($localization))
            ->contains(fn (string $token) => ! $localization->has($token));

        return $inheritsDate || $inheritsField;
    }

    /**
     * The localization's date follows the origin. A localized date on an
     * entry between them breaks the chain.
     */
    private function inheritsDate(Entry $origin, Entry $localization): bool
    {
        $entry = $localization;

        while ($entry->id() !== $origin->id()) {
            if (! $entry->hasOrigin() || $this->hasLocalizedDate($entry)) {
                return false;
            }

            $entry = $entry->origin();
        }

        return true;
    }

    /**
     * True when this entry stores a date of its own.
     */
    private function hasLocalizedDate(Entry $entry): bool
    {
        // Flat file: an inherited date is absent from the filename.
        if ($entry->initialPath()) {
            return $entry->hasExplicitDate();
        }

        // Eloquent: the origin date is copied onto the row unless date is
        // one of the localized fields. A custom file entry may also define
        // model(), so only read it when there is no file path.
        return collect($entry->model()->getAttribute('__localized_fields'))->contains('date');
    }

    /**
     * Stash the site-relative path still stored for each published entry.
     * An id is looked up. An entry is used as given.
     *
     * @param  array<int, Entry|string>  $entries
     * @return array<string, string>
     */
    private function stashOriginalPaths(array $entries): array
    {
        $paths = [];

        foreach ($entries as $entry) {
            if (is_string($entry)) {
                $entry = EntryFacade::find($entry);
            }

            if (! $entry) {
                continue;
            }

            if (! $this->isRedirectable($entry)) {
                continue;
            }

            if (! $path = $this->originalPath($entry)) {
                continue;
            }

            $paths[$entry->id()] = $path;
        }

        return $paths;
    }

    /**
     * Original site-relative path, read from storage rather than computed.
     *
     * By the time a tree move is observable, the in-memory
     * entry already resolves to the new URL (and a fresh request has an empty
     * Blink cache, so uri() cannot be trusted to return the old one). The
     * Stache URI index and the Eloquent uri column are still the previous
     * value until Statamic rewrites them after the save.
     */
    private function originalPath(Entry $entry): ?string
    {
        $initialPath = $entry->initialPath();

        // Flat-file entries keep the previous URI in the Stache index.
        if ($initialPath) {
            $uri = Stache::store('entries')
                ->store($entry->collectionHandle())
                ->index('uri')
                ->get($entry->id());
        }

        // Eloquent entries keep it on the uri column. A custom file entry
        // may also define model(), so only read it when there is no file path.
        if (! $initialPath && method_exists($entry, 'model')) {
            $uri = $entry->model()->getAttribute('uri');
        }

        return is_string($uri) && $uri !== '' ? $uri : null;
    }

    /**
     * Stash localization slug overrides that differ from the term now.
     * A null slug means that site inherited the base slug.
     */
    private function stashTerm(Term $term): void
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
        if ($data === null && ! $term->getOriginal('slug')) {
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
     * The tree is already the new shape, but stored URIs still describe the
     * previous one. Stash those paths and create the redirects once Statamic
     * has rewritten them on CollectionTreeSaved.
     *
     * @param  array<int, string>  $ids
     */
    private function stashMovedEntries(array $ids): void
    {
        if (! RedirectsFeature::enabled()) {
            return;
        }

        if (! $entry = EntryFacade::query()->whereIn('id', $ids)->first()) {
            return;
        }

        $this->movedEntries[$entry->collectionHandle().'::'.$entry->locale()] = $this->stashOriginalPaths($ids);
    }

    /**
     * Redirect the entry from the path stashed on save.
     */
    private function redirectEntry(Entry $entry): void
    {
        $originalPath = $this->entries[$entry->id()] ?? null;

        unset($this->entries[$entry->id()]);

        $this->processEntryRedirect($entry, $originalPath);
    }

    /**
     * Statamic does not rewrite child page URIs on this save, so do that
     * before redirecting from the paths stashed above.
     */
    private function redirectChildPages(Entry $entry): void
    {
        $paths = $this->childPages[$entry->id()] ?? null;

        unset($this->childPages[$entry->id()]);

        if (! $paths) {
            return;
        }

        // TODO: Drop this call once Statamic rewrites child URIs on the parent save.
        $this->rewriteChildPageUris($entry, array_keys($paths));

        foreach ($paths as $id => $originalPath) {
            if (! $child = EntryFacade::find($id)) {
                continue;
            }

            $this->processEntryRedirect($child, $originalPath);
        }
    }

    /**
     * TODO: Statamic should have rewritten these URIs in updateChildPageUris()
     * before EntrySaved. It doesn't: Entry::save() syncOriginal()s the
     * cached entry, so shouldUpdateUris() is false. Drop this method once
     * that bug is fixed. Creating the redirects stays.
     *
     * @param  array<int, string>  $ids
     */
    private function rewriteChildPageUris(Entry $entry, array $ids): void
    {
        Blink::store('entry-uris')->flush();
        Blink::store('structure-uris')->flush();
        Blink::store('structure-entries')->flush();
        Blink::store('structure-entries')->put($entry->id(), $entry);

        $entry->collection()->updateEntryUris($ids);

        Blink::store('entry-uris')->flush();
        Blink::store('structure-uris')->flush();
    }

    /**
     * The base slug is still on getOriginal() at TermSaved. Localization
     * overrides were stashed on TermSaving. Terms carry no date and can't be
     * an entry:: destination, so the redirect points at the new path.
     *
     * A base-slug change flows to every localization that inherits it, so a
     * redirect is created per site. A localization whose own slug changed is
     * redirected on its own. An unchanged override drops out (old URL equals
     * new URL).
     */
    private function redirectTerm(Term $term): void
    {
        if (! $this->isRedirectable($term)) {
            return;
        }

        $currentPaths = $this->pathsPerSite($term);

        // A term owns its URL in every site, so delete any automatic redirect that uses it.
        foreach ($currentPaths as $site => $currentPath) {
            $this->deleteAutomaticRedirectOnLiveUrl($currentPath, $site);
        }

        $originalSlug = $term->getOriginal('slug');
        $newSlug = $term->slug();
        $originalSlugs = $this->terms[$term->id()] ?? [];

        unset($this->terms[$term->id()]);

        $baseSlugChanged = $originalSlug && $originalSlug !== $newSlug;

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

        $originalPaths = $this->pathsPerSite($term);

        if ($baseSlugChanged) {
            $term->slug($newSlug);
        }

        foreach ($currentSlugs as $site => $slug) {
            $term->in($site)->set('slug', $slug);
        }

        foreach ($currentPaths as $site => $currentPath) {
            $originalPath = $originalPaths[$site] ?? null;

            if (! $originalPath || $originalPath === $currentPath) {
                continue;
            }

            if ($this->sourceIsOwnedByPublishedContent($originalPath, $site)) {
                continue;
            }

            $this->createRedirect($originalPath, $currentPath, $site);
            $this->repointStaleRedirectDestinations($originalPath, $currentPath, $site);
        }
    }

    /**
     * The site-relative path of the term in each of its localizations, in the
     * term's current state.
     *
     * @return array<string, string>
     */
    private function pathsPerSite(Term $term): array
    {
        return $term->taxonomy()->sites()
            ->mapWithKeys(fn ($site) => [$site => $term->in($site)->uri()])
            ->all();
    }

    /**
     * Runs after Statamic's UpdateStructuredEntryUris listener, which is
     * registered earlier. Forget the URI caches anyway so a stale Blink
     * value from before the move can't hide the new path.
     */
    private function redirectMovedEntries(CollectionTree $tree): void
    {
        if (! RedirectsFeature::enabled()) {
            return;
        }

        $key = $tree->handle().'::'.$tree->locale();
        $paths = $this->movedEntries[$key] ?? [];

        unset($this->movedEntries[$key]);

        if (empty($paths)) {
            return;
        }

        Blink::store('entry-uris')->flush();
        Blink::store('structure-uris')->flush();

        foreach ($paths as $id => $originalPath) {
            if (! $entry = EntryFacade::find($id)) {
                continue;
            }

            $this->processEntryRedirect($entry, $originalPath);
        }
    }

    /**
     * Delete automatic redirects that use the entry's current URL, and when
     * an original path was stashed, create the redirect from it.
     */
    private function processEntryRedirect(Entry $entry, ?string $originalPath = null): void
    {
        if (! $this->isRedirectable($entry)) {
            return;
        }

        if (! $currentPath = $this->currentPath($entry)) {
            return;
        }

        $this->deleteAutomaticRedirectOnLiveUrl($currentPath, $entry->locale());

        if (! $originalPath || $originalPath === $currentPath) {
            return;
        }

        if ($this->sourceIsOwnedByPublishedContent($originalPath, $entry->locale())) {
            return;
        }

        $this->createRedirect($originalPath, "entry::{$entry->id()}", $entry->locale());
        $this->repointStaleRedirectDestinations($originalPath, "entry::{$entry->id()}", $entry->locale());
    }

    private function currentPath(Entry $entry): ?string
    {
        // A URI blinked before this change would hide the new path.
        Blink::store('entry-uris')->forget($entry->id());

        $url = $entry->absoluteUrlWithoutRedirect();

        return $url ? $entry->site()->relativePath($url) : null;
    }

    /**
     * Delete auto-created redirects whose source is this URL. The URL now
     * belongs to a live page, so the redirect would send the request away.
     * Manual redirects are the editor's to keep.
     */
    private function deleteAutomaticRedirectOnLiveUrl(string $source, string $site): void
    {
        RedirectFacade::query()
            ->where('site', $site)
            ->whereSource($source)
            ->where('origin', RedirectOrigin::Automatic->value)
            ->get()
            ->each(fn (Redirect $redirect) => $redirect->delete());
    }

    /**
     * A source still owned by published content must remain routable and
     * must not be claimed by an automatic redirect.
     */
    private function sourceIsOwnedByPublishedContent(string $source, string $site): bool
    {
        return $this->sourceIsOwnedByPublishedEntry($source, $site)
            || $this->sourceIsOwnedByPublishedTerm($source, $site);
    }

    private function sourceIsOwnedByPublishedEntry(string $source, string $site): bool
    {
        return EntryFacade::query()
            ->where('site', $site)
            ->where('uri', $source)
            ->whereStatus('published')
            ->exists();
    }

    private function sourceIsOwnedByPublishedTerm(string $source, string $site): bool
    {
        return TermFacade::findByUri($source, $site)?->status() === 'published';
    }

    /**
     * Create the redirect from the old to the new URL. An existing redirect
     * with the same source is a deliberate editor decision and wins.
     */
    private function createRedirect(string $source, string $destination, string $site): void
    {
        $existing = RedirectFacade::query()
            ->where('site', $site)
            ->whereSource($source)
            ->first();

        if ($existing) {
            return;
        }

        RedirectFacade::make()
            ->source($source)
            ->destination($destination)
            ->responseCode(RedirectResponseCode::Permanent)
            ->site($site)
            ->origin(RedirectOrigin::Automatic)
            ->save();
    }

    /**
     * An auto-created redirect whose destination is the old path is now stale:
     * following it would take two hops (A → old → new). Point it straight at the
     * new destination. Only auto-created redirects are repointed; manual ones are
     * the editor's to keep. Entry redirects use an entry reference that never goes
     * stale, so in practice this only flattens term chains.
     */
    private function repointStaleRedirectDestinations(string $staleDestination, string $destination, string $site): void
    {
        RedirectFacade::query()
            ->where('site', $site)
            ->where('destination', $staleDestination)
            ->where('origin', RedirectOrigin::Automatic->value)
            ->get()
            ->each(fn (Redirect $redirect) => $redirect->destination($destination)->save());
    }

    /**
     * A deleted entry leaves the auto-created redirects that pointed at it with
     * a dead destination, so remove them. Manual redirects and redirects whose
     * source is the now-gone URL are left alone.
     */
    private function deleteEntry(Entry $entry): void
    {
        if (! RedirectsFeature::enabled()) {
            return;
        }

        $this->deleteAutomaticRedirectsTo("entry::{$entry->id()}");
    }

    /**
     * A deleted term does the same, but its auto-created redirects point at a
     * path rather than an entry reference, so match on the term's URL per site.
     */
    private function deleteTerm(Term $term): void
    {
        if (! RedirectsFeature::enabled()) {
            return;
        }

        foreach ($this->pathsPerSite($term) as $site => $path) {
            $this->deleteAutomaticRedirectsTo($path, $site);
        }
    }

    /**
     * Delete the auto-created redirects that point at a given destination,
     * used to clear redirects left dangling by a deleted entry or term.
     */
    private function deleteAutomaticRedirectsTo(string $destination, ?string $site = null): void
    {
        RedirectFacade::query()
            ->when($site, fn ($query) => $query->where('site', $site))
            ->where('destination', $destination)
            ->where('origin', RedirectOrigin::Automatic->value)
            ->get()
            ->each(fn (Redirect $redirect) => $redirect->delete());
    }
}
