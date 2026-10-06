<?php

namespace Aerni\AdvancedSeo\Listeners;

use Aerni\AdvancedSeo\Contracts\Redirect;
use Aerni\AdvancedSeo\Enums\RedirectOrigin;
use Aerni\AdvancedSeo\Enums\RedirectResponseCode;
use Aerni\AdvancedSeo\Facades\Redirect as RedirectFacade;
use Aerni\AdvancedSeo\Facades\Seo;
use Aerni\AdvancedSeo\Features\Redirects as RedirectsFeature;
use Aerni\AdvancedSeo\Redirects\EntryUrlValues;
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
use Statamic\Facades\YAML;

/**
 * Creates and maintains redirects automatically as entry and term URLs change.
 *
 * Capture is keyed on the slug, the date, and any other route token that reads
 * an entry field. On the flat-file driver that field's previous value is read
 * from the file, because getOriginal() already returns the new value. The
 * Eloquent driver keeps getOriginal() reliable. A tree move never
 * saves the entry, so the previous URI is read from storage
 * (CollectionTreeEntriesMovedOrRemoved still sees it; CollectionTreeSaved
 * runs after Statamic has written the new one). A parent slug, date, or route
 * token change does not save the descendants, so their previous URIs are
 * stashed on the parent save and their stored URIs are rewritten before the
 * redirects are created.
 *
 * URL changes from any other source are out of scope and left to the editor
 * to redirect manually:
 *
 * - a collection route or mount change, which changes every entry's URL at once.
 */
class HandleAutomaticRedirects
{
    /**
     * Stash the original paths before the save overwrites them, so
     * handleEntrySaved can create the redirects.
     */
    public function handleEntrySaving(EntrySaving $event): void
    {
        $this->stashEntry($event->entry);
        $this->stashDescendants($event->entry);
    }

    /**
     * Once the save went through, redirect the entry and its descendants
     * from the paths stashed above.
     */
    public function handleEntrySaved(EntrySaved $event): void
    {
        $this->redirectEntry($event->entry);
        $this->redirectDescendants($event->entry);
    }

    /**
     * The tree is already the new shape, but stored URIs still describe the
     * previous one. Stash those paths and create the redirects once Statamic
     * has rewritten them on CollectionTreeSaved.
     */
    public function handleCollectionTreeEntriesMovedOrRemoved(CollectionTreeEntriesMovedOrRemoved $event): void
    {
        if (! RedirectsFeature::enabled()) {
            return;
        }

        $this->stashOriginalPaths($event->moved, $this->treeBlinkKey());
    }

    /**
     * Runs after Statamic's UpdateStructuredEntryUris listener, which is
     * registered earlier. Forget the URI caches anyway so a stale Blink
     * value from before the move can't hide the new path.
     */
    public function handleCollectionTreeSaved(CollectionTreeSaved $event): void
    {
        if (! RedirectsFeature::enabled()) {
            return;
        }

        if (! $paths = Blink::pull($this->treeBlinkKey())) {
            return;
        }

        Blink::store('entry-uris')->flush();
        Blink::store('structure-uris')->flush();

        foreach ($paths as $id => $originalPath) {
            $this->processEntryRedirect(EntryFacade::find($id), $originalPath);
        }
    }

    /**
     * A deleted entry leaves the auto-created redirects that pointed at it with
     * a dead destination, so remove them. Manual redirects and redirects whose
     * source is the now-gone URL are left alone.
     */
    public function handleEntryDeleted(EntryDeleted $event): void
    {
        if (! RedirectsFeature::enabled()) {
            return;
        }

        $this->deleteAutomaticRedirectsTo("entry::{$event->entry->id()}");
    }

    /**
     * A localization's overridden slug lives in that site's data, which
     * getOriginal() does not track. Stash the previous overrides while the
     * file or Eloquent model still has them.
     */
    public function handleTermSaving(TermSaving $event): void
    {
        $this->stashTerm($event->term);
    }

    /**
     * Once the save went through, redirect each site whose URL changed.
     */
    public function handleTermSaved(TermSaved $event): void
    {
        $this->redirectTerm($event->term);
    }

    /**
     * A deleted term does the same, but its auto-created redirects point at a
     * path rather than an entry reference, so match on the term's URL per site.
     */
    public function handleTermDeleted(TermDeleted $event): void
    {
        if (! RedirectsFeature::enabled()) {
            return;
        }

        foreach ($this->pathsPerSite($event->term) as $site => $path) {
            $this->deleteAutomaticRedirectsTo($path, $site);
        }
    }

    /**
     * The feature flag and the collection or taxonomy toggle.
     */
    protected function redirectsEnabled(Entry|Term $item): bool
    {
        if (! RedirectsFeature::enabled()) {
            return false;
        }

        $handle = match (true) {
            $item instanceof Entry => "collections::{$item->collectionHandle()}",
            $item instanceof Term => "taxonomies::{$item->taxonomyHandle()}",
        };

        return (bool) Seo::find($handle)?->config()->value('redirects');
    }

    /**
     * Redirects are enabled and this item is published. Descendants are
     * checked on their own, so a false result here does not stop the rest
     * of the save.
     */
    protected function isRedirectable(Entry|Term $item): bool
    {
        if (! $this->redirectsEnabled($item)) {
            return false;
        }

        // Only a live item owns its URL; draft, scheduled and expired entries
        // aren't publicly reachable. Terms are always published.
        return $item->status() === 'published';
    }

    /**
     * Stash this entry's original URL when its slug, date, or a route field
     * changed and the entry itself should get a redirect.
     */
    protected function stashEntry(Entry $entry): void
    {
        if (! $this->isRedirectable($entry)) {
            return;
        }

        if (! $changed = $this->changedValues($entry)) {
            return;
        }

        if (! $originalUrl = $this->originalUrl($entry, $changed)) {
            return;
        }

        Blink::put($this->entryBlinkKey($entry), $entry->site()->relativePath($originalUrl));
    }

    /**
     * A parent slug, date, or route field change moves every descendant.
     * Stash the path still stored for each published one.
     */
    protected function stashDescendants(Entry $entry): void
    {
        $page = $entry->page();

        if (! $page) {
            return;
        }

        if (! $this->changedValues($entry)) {
            return;
        }

        $this->stashOriginalPaths(
            $page->flattenedPages()->map->id()->all(),
            $this->descendantBlinkKey($entry),
        );
    }

    /**
     * Previous values that differ from the entry now.
     * Null when this save does not move a URL.
     */
    protected function changedValues(Entry $entry): ?EntryUrlValues
    {
        if (! $original = $this->originalValues($entry)) {
            return null;
        }

        return $original->diff($this->currentValues($entry, $original));
    }

    /**
     * Slug, date, and entry data from before this save.
     * Null when the entry has never been saved.
     */
    protected function originalValues(Entry $entry): ?EntryUrlValues
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

        // No file and no previous slug: the entry has never been saved.
        if (! $slug = $entry->getOriginal('slug')) {
            return null;
        }

        // Eloquent: there is no file, so getOriginal() is still the pre-save slug, date, and data.
        return new EntryUrlValues(
            slug: $slug,
            date: $entry->getOriginal('date'),
            data: $this->routeFieldValues($entry),
        );
    }

    /**
     * Route-field values from the file, or from getOriginal() when there is no file.
     * Null when the route has no field tokens, so the file is left unread.
     *
     * @return array<string, mixed>|null
     */
    protected function routeFieldValues(Entry $entry): ?array
    {
        if (! $tokens = $this->routeFieldTokens($entry)) {
            return null;
        }

        // Flat file reads the file. Eloquent reads getOriginal().
        $source = ($path = $entry->initialPath())
            ? YAML::file($path)->parse()
            : $entry->getOriginal();

        $data = [];

        foreach ($tokens as $token) {
            $data[$token] = $source[$token] ?? null;
        }

        return $data;
    }

    /**
     * Route tokens that read an entry field, ignoring slug, date, and the
     * structure tokens Statamic fills in itself.
     * Null when the route has none.
     *
     * @return array<int, string>|null
     */
    protected function routeFieldTokens(Entry $entry): ?array
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
    protected function currentValues(Entry $entry, EntryUrlValues $original): EntryUrlValues
    {
        $data = null;

        foreach (array_keys($original->data ?? []) as $token) {
            $data[$token] = $entry->get($token);
        }

        return new EntryUrlValues(
            slug: $entry->slug(),
            date: $original->date ? $this->currentDate($entry, $original->date) : null,
            data: $data,
        );
    }

    /**
     * The current date formatted at the original date's precision,
     * so the two can be compared as strings.
     */
    protected function currentDate(Entry $entry, string $originalDate): ?string
    {
        return $entry->date()?->format(match (strlen($originalDate)) {
            15 => 'Y-m-d-Hi',
            17 => 'Y-m-d-His',
            default => 'Y-m-d',
        });
    }

    /**
     * Let the routing compute the URL the entry had before this save by
     * temporarily restoring the previous slug, date, and route fields. The
     * cached uri has to be flushed for the swapped values to take effect.
     */
    protected function originalUrl(Entry $entry, EntryUrlValues $values): ?string
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

    protected function entryBlinkKey(Entry $entry): string
    {
        return "advanced-seo::automatic-redirect::{$entry->id()}";
    }

    protected function descendantBlinkKey(Entry $entry): string
    {
        return "advanced-seo::automatic-redirect-descendants::{$entry->id()}";
    }

    /**
     * Redirect the entry from the path stashed on save.
     */
    protected function redirectEntry(Entry $entry): void
    {
        $this->processEntryRedirect($entry, Blink::pull($this->entryBlinkKey($entry)));
    }

    /**
     * Statamic does not rewrite descendant URIs on this save, so do that
     * before redirecting from the paths stashed above.
     */
    protected function redirectDescendants(Entry $entry): void
    {
        if (! $paths = Blink::pull($this->descendantBlinkKey($entry))) {
            return;
        }

        // TODO: Drop this call once Statamic rewrites child URIs on the parent save.
        $this->rewriteDescendantUris($entry, array_keys($paths));

        foreach ($paths as $id => $originalPath) {
            $this->processEntryRedirect(EntryFacade::find($id), $originalPath);
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
    protected function rewriteDescendantUris(Entry $entry, array $ids): void
    {
        Blink::store('entry-uris')->flush();
        Blink::store('structure-uris')->flush();
        Blink::store('structure-entries')->flush();
        Blink::store('structure-entries')->put($entry->id(), $entry);

        $entry->collection()->updateEntryUris($ids);

        Blink::store('entry-uris')->flush();
        Blink::store('structure-uris')->flush();
    }

    protected function treeBlinkKey(): string
    {
        return 'advanced-seo::automatic-redirect-tree';
    }

    /**
     * Stash the site-relative path still stored for each published entry.
     *
     * @param  array<int, string>  $ids
     */
    protected function stashOriginalPaths(array $ids, string $blinkKey): void
    {
        $paths = [];

        foreach ($ids as $id) {
            if (! $entry = EntryFacade::find($id)) {
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

        Blink::put($blinkKey, $paths);
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
    protected function originalPath(Entry $entry): ?string
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
            $uri = $entry->model()?->getAttribute('uri');
        }

        return is_string($uri) && $uri !== '' ? $uri : null;
    }

    /**
     * Delete automatic redirects that use the entry's current URL, and when
     * an original path was stashed, create the redirect from it.
     */
    protected function processEntryRedirect(Entry $entry, ?string $originalPath = null): void
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

        if ($this->sourceIsOwnedByPublishedEntry($originalPath, $entry->locale())) {
            return;
        }

        $this->createRedirect($originalPath, "entry::{$entry->id()}", $entry->locale());
        $this->repointStaleRedirectDestinations($originalPath, "entry::{$entry->id()}", $entry->locale());
    }

    protected function currentPath(Entry $entry): ?string
    {
        // A URI blinked before this change would hide the new path.
        Blink::store('entry-uris')->forget($entry->id());

        $url = $entry->absoluteUrlWithoutRedirect();

        return $url ? $entry->site()->relativePath($url) : null;
    }

    /**
     * A source still owned by a published entry must remain routable and
     * must not be claimed by an automatic redirect.
     */
    protected function sourceIsOwnedByPublishedEntry(string $source, string $site): bool
    {
        return EntryFacade::query()
            ->where('site', $site)
            ->where('uri', $source)
            ->whereStatus('published')
            ->exists();
    }

    /**
     * Stash localization slug overrides that differ from the term now.
     * A null slug means that site inherited the base slug.
     */
    protected function stashTerm(Term $term): void
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

        Blink::put($this->termBlinkKey($term), $changed);
    }

    /**
     * Localization slug overrides from before this save, keyed by site.
     * Null when the term has never been saved. A null slug means that site
     * inherited the base slug.
     *
     * @return array<string, string|null>|null
     */
    protected function originalLocalizationSlugs(Term $term): ?array
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
            $data = $term->model()?->getAttribute('data');
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

    protected function termBlinkKey(Term $term): string
    {
        return "advanced-seo::automatic-redirect-term::{$term->id()}";
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
    protected function redirectTerm(Term $term): void
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
        $originalSlugs = Blink::pull($this->termBlinkKey($term)) ?? [];
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
    protected function pathsPerSite(Term $term): array
    {
        return $term->taxonomy()->sites()
            ->mapWithKeys(fn ($site) => [$site => $term->in($site)->uri()])
            ->all();
    }

    /**
     * Create the redirect from the old to the new URL. An existing redirect
     * with the same source is a deliberate editor decision and wins.
     */
    protected function createRedirect(string $source, string $destination, string $site): void
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
    protected function repointStaleRedirectDestinations(string $staleDestination, string $destination, string $site): void
    {
        RedirectFacade::query()
            ->where('site', $site)
            ->where('destination', $staleDestination)
            ->where('origin', RedirectOrigin::Automatic->value)
            ->get()
            ->each(fn (Redirect $redirect) => $redirect->destination($destination)->save());
    }

    /**
     * Delete auto-created redirects whose source is this URL. The URL now
     * belongs to a live page, so the redirect would send the request away.
     * Manual redirects are the editor's to keep.
     */
    protected function deleteAutomaticRedirectOnLiveUrl(string $source, string $site): void
    {
        RedirectFacade::query()
            ->where('site', $site)
            ->whereSource($source)
            ->where('origin', RedirectOrigin::Automatic->value)
            ->get()
            ->each(fn (Redirect $redirect) => $redirect->delete());
    }

    /**
     * Delete the auto-created redirects that point at a given destination,
     * used to clear redirects left dangling by a deleted entry or term.
     */
    protected function deleteAutomaticRedirectsTo(string $destination, ?string $site = null): void
    {
        RedirectFacade::query()
            ->when($site, fn ($query) => $query->where('site', $site))
            ->where('destination', $destination)
            ->where('origin', RedirectOrigin::Automatic->value)
            ->get()
            ->each(fn (Redirect $redirect) => $redirect->delete());
    }
}
