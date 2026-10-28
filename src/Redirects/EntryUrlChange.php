<?php

namespace Aerni\AdvancedSeo\Redirects;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Statamic\Contracts\Entries\Entry;
use Statamic\Entries\GetDateFromPath;
use Statamic\Entries\GetSlugFromPath;
use Statamic\Facades\Antlers;
use Statamic\Facades\Blink;
use Statamic\Facades\Stache;
use Statamic\Facades\URL;
use Statamic\Facades\YAML;

class EntryUrlChange
{
    /**
     * Capture the path before saving. The final URL comparison runs after saving.
     */
    public function originalPath(Entry $entry): ?string
    {
        $changed = $this->changedValues($entry);
        $url = $this->isEloquentEntry($entry)
            ? $this->eloquentOriginalUrl($entry)
            : $this->stacheOriginalUrl($entry, $changed);

        return $url ? $entry->site()->relativePath($url) : null;
    }

    /**
     * Compare the captured path with the final URL after Statamic saves or rewrites it.
     */
    public function compare(Entry $entry, ?string $originalPath): EntryUrlChangeResult
    {
        $result = new EntryUrlChangeResult($originalPath, $this->currentPath($entry));

        // Descendant saves may have read the origin's previous state again.
        $this->forget($entry);

        return $result;
    }

    private function currentPath(Entry $entry): ?string
    {
        Blink::store('entry-uris')->forget($entry->id());
        Blink::store('structure-uris')->forget($entry->id());

        $url = $entry->absoluteUrlWithoutRedirect();

        return $url ? $entry->site()->relativePath($url) : null;
    }

    /**
     * Select candidates for child and mount URI rewrites. Actual moves are
     * determined by compare() after those rewrites, not by field differences.
     */
    public function mayAffectRelatedEntries(Entry $entry): bool
    {
        return ! $this->changedValues($entry)->isEmpty();
    }

    /**
     * Localizations that inherit route values affected by this save.
     *
     * @return Collection<int|string, Entry>
     */
    public function affectedLocalizations(Entry $entry): Collection
    {
        $changed = $this->changedValues($entry);

        if (! $changed->data && ! $changed->date) {
            return collect();
        }

        return $entry->descendants()
            ->filter(fn (Entry $localization) => $this->inheritsChangedValues($entry, $localization));
    }

    /**
     * Call after capturing the localization's old paths. Eloquent copies an
     * inherited date onto its row; clear it so its save uses the origin's new date.
     */
    public function prepareLocalizationForSave(Entry $origin, Entry $localization): void
    {
        if ($this->changedValues($origin)->date && $this->isEloquentEntry($localization) && $this->inheritsDate($origin, $localization)) {
            $localization->date(null);
        }
    }

    /**
     * Stored paths remain authoritative during tree and mount moves, whose
     * in-memory entries may already resolve to their new URLs.
     */
    public function storedPath(Entry $entry): ?string
    {
        if ($entry->initialPath()) {
            $uri = Stache::store('entries')->store($entry->collectionHandle())->index('uri')->get($entry->id());
        } elseif ($this->isEloquentEntry($entry)) {
            $uri = $entry->model()->getAttribute('uri');
        } else {
            return null;
        }

        return is_string($uri) && $uri !== '' ? $uri : null;
    }

    /**
     * The entry was publicly reachable before this save. The status on the
     * entry itself is already the new one.
     */
    public function wasLive(Entry $entry): bool
    {
        if (! $this->wasPublished($entry)) {
            return false;
        }

        $date = $this->parseDate($this->originalDate($entry));

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

    public function forget(Entry $entry): void
    {
        Blink::forget($this->changedBlinkKey($entry));
        Blink::forget($this->dataBlinkKey($entry));
        Blink::forget($this->urlBlinkKey($entry));
        Blink::forget($this->dateBlinkKey($entry));
    }

    /**
     * Previous values that differ from the entry now.
     * Route fields include tokens used by a localization route.
     * Empty when none of those values changed.
     */
    private function changedValues(Entry $entry): EntryUrlValues
    {
        // The entry, its child pages, and its localizations each ask during this save.
        return Blink::once($this->changedBlinkKey($entry), function () use ($entry) {
            $original = $this->originalValues($entry);
            $current = $this->currentValues($entry, $original);

            $changed = $original->diff($current);

            return new EntryUrlValues(
                slug: $changed->slug,
                date: $this->changedDate($entry, $original, $changed),
                data: $changed->data,
            );
        });
    }

    /**
     * Stache: the file still contains the old data; getOriginal() has already
     * been replaced. Eloquent: there is no file, so getOriginal() still contains
     * the pre-save data. Reuse the snapshot until this entry's save has been stashed.
     *
     * @return array<string, mixed>
     */
    private function originalData(Entry $entry): array
    {
        return Blink::once($this->dataBlinkKey($entry), fn () => ($path = $entry->initialPath())
            ? YAML::file($path)->parse()
            : $entry->getOriginal());
    }

    /**
     * Keep the previous date until this entry's own save is stashed. Its origin
     * may already have been saved before a localization reaches EntrySaving.
     */
    private function originalDate(Entry $entry): ?string
    {
        return Blink::once($this->dateBlinkKey($entry), fn () => $this->resolveOriginalDate($entry));
    }

    private function resolveOriginalDate(Entry $entry): ?string
    {
        if ($this->isEloquentEntry($entry)) {
            return $this->eloquentOriginalDate($entry);
        }

        // Flat file: the previous date is still in the filename. A localization
        // that inherits its date has none, so the origin's stored date is used.
        // getOriginal() has already been replaced.
        if ($date = (new GetDateFromPath)($entry->initialPath())) {
            return $date;
        }

        if ($entry->hasOrigin()) {
            return $this->originalDate($entry->origin());
        }

        // A dated entry with no date in the filename is still public at its modified time.
        if ($entry->collection()->dated()) {
            return $this->lastModifiedDate($entry);
        }

        return null;
    }

    /**
     * Whether the entry was published before this save. The flag on the entry
     * itself is already the new one.
     */
    private function wasPublished(Entry $entry): bool
    {
        return (bool) ($this->originalData($entry)['published'] ?? (bool) $entry->initialPath());
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
     * True when a changed route field or date is absent on the localization,
     * so it uses the origin's value. A route field only counts when this
     * localization's route uses it.
     */
    private function inheritsChangedValues(Entry $origin, Entry $localization): bool
    {
        $changed = $this->changedValues($origin);

        $inheritsDate = $changed->date && $this->inheritsDate($origin, $localization);

        $inheritsField = collect($changed->data)
            ->keys()
            ->intersect($this->routeFieldTokens($localization))
            ->contains(fn (string $token) => ! $localization->has($token));

        return $inheritsDate || $inheritsField;
    }

    /**
     * Blink key for the changed values of one entry save.
     */
    private function changedBlinkKey(Entry $entry): string
    {
        return "advanced-seo::automatic-redirects::changed::{$entry->id()}::{$entry->locale()}";
    }

    private function dataBlinkKey(Entry $entry): string
    {
        return "advanced-seo::automatic-redirects::data::{$entry->id()}::{$entry->locale()}";
    }

    private function dateBlinkKey(Entry $entry): string
    {
        return "advanced-seo::automatic-redirects::date::{$entry->id()}::{$entry->locale()}";
    }

    private function urlBlinkKey(Entry $entry): string
    {
        return "advanced-seo::automatic-redirects::url::{$entry->id()}::{$entry->locale()}";
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
            $slug = (new GetSlugFromPath)($path);

            // A collection without slugs names the file after the id. That is no slug.
            if (! $entry->collection()->requiresSlugs() && $slug === $entry->id()) {
                $slug = null;
            }

            return new EntryUrlValues(
                slug: $slug,
                date: $this->originalDate($entry),
                data: $this->routeFieldValues($entry),
            );
        }

        // First save. getOriginal() is still empty, and an Eloquent entry has no
        // model yet. An empty result means this save changed nothing.
        if (! array_key_exists('slug', $entry->getOriginal())) {
            return new EntryUrlValues;
        }

        return new EntryUrlValues(
            slug: $entry->getOriginal('slug'),
            date: $this->originalDate($entry),
            data: $this->routeFieldValues($entry),
        );
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

        $source = $this->originalData($entry);

        // A stored null is its own value. An absent field inherits, so compare
        // the origin value instead of collapsing both to null.
        return $tokens
            ->mapWithKeys(function (string $token) use ($entry, $source) {
                if (array_key_exists($token, $source)) {
                    return [$token => $source[$token]];
                }

                return [$token => $this->inheritedOriginalRouteValue($entry, $token)];
            })
            ->all();
    }

    /**
     * Resolve missing fields from the origin's saved snapshot. Its in-memory
     * value can already be new while a localization is being saved.
     */
    private function inheritedOriginalRouteValue(Entry $entry, string $token): mixed
    {
        if (! $entry->hasOrigin()) {
            return null;
        }

        $origin = $entry->origin();
        $source = $this->originalData($origin);

        return array_key_exists($token, $source)
            ? $source[$token]
            : $this->inheritedOriginalRouteValue($origin, $token);
    }

    /**
     * The origin value a missing route field inherits. Null when this entry
     * is the root.
     */
    private function inheritedRouteValue(Entry $entry, string $token): mixed
    {
        return $entry->hasOrigin() ? $entry->origin()->value($token) : null;
    }

    /**
     * Route tokens that read an entry field. Statamic fills in slug and
     * structure tokens itself, and date tokens only on dated entries.
     * Null when the route has none.
     *
     * @return array<int, string>|null
     */
    private function routeFieldTokens(Entry $entry): ?array
    {
        if (! $route = $entry->route()) {
            return null;
        }

        // A route token is a single pair of braces, optionally with a modifier: {title | slugify}.
        // Turn that into Antlers so the field name can be read. Leave {{ }} untouched.
        $route = preg_replace('/(?<!{){\s*([a-zA-Z0-9_\-]+)(?:\s*\|[^}]*)?\s*}(?!})/', '{{ $1 }}', $route);

        $tokens = collect(Antlers::identifiers($route))
            ->reject(fn (string $token) => in_array($token, [
                'slug', 'parent_uri', 'mount', 'depth', 'is_root', 'id', 'published',
            ]))
            ->reject(fn (string $token) => $entry->hasDate() && in_array($token, ['date', 'year', 'month', 'day']))
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
            if ($token === 'title' && $entry->collection()->autoGeneratesTitles()) {
                $data[$token] = $entry->autoGeneratedTitle();

                continue;
            }

            // A stored value, including null, is local. An absent field inherits.
            $data[$token] = $entry->has($token)
                ? $entry->get($token)
                : $this->inheritedRouteValue($entry, $token);
        }

        // Formatting the UTC date directly can show the day before the one in the filename.
        $date = $original->date ? $entry->date()?->copy()->setTimezone(config('app.timezone'))->format(match (strlen($original->date)) {
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
     * Include date changes hidden by identical formatted times during DST.
     * Capture the Stache URI even for known changes: the filename loses the offset.
     */
    private function changedDate(Entry $entry, EntryUrlValues $original, EntryUrlValues $changed): ?string
    {
        if ($original->date === null) {
            return null;
        }

        $eloquent = $this->isEloquentEntry($entry);
        $url = $eloquent ? $this->eloquentOriginalUrl($entry) : $this->stacheIndexedOriginalUrl($entry);

        if ($changed->date !== null) {
            return $changed->date;
        }

        if ($eloquent && $entry->isDirty('date')) {
            return $original->date;
        }

        return $url && $this->dateChangedInUrl($entry, $original, $changed, $url)
            ? $original->date
            : null;
    }

    /**
     * Hold the other route values at their saved values so a slug or field
     * edit cannot be mistaken for a hidden date change. Keep the current
     * date object, which still distinguishes the two repeated-hour instants.
     */
    private function dateChangedInUrl(Entry $entry, EntryUrlValues $original, EntryUrlValues $changed, string $originalUrl): bool
    {
        $copy = clone $entry;
        $copy->slug($original->slug);
        $copy->data($entry->data()->merge($changed->data ?? []));

        $comparison = new EntryUrlChangeResult(
            $entry->site()->relativePath($originalUrl),
            $this->currentPath($copy),
        );

        $this->restoreEntryAfterCopy($entry);

        return $comparison->moved();
    }

    /**
     * The public date when the filename has none. Statamic uses the last modified time.
     */
    private function lastModifiedDate(Entry $entry): string
    {
        // Both the date and updated_at may already have changed on the entry.
        // Restore the stored data before resolving the fallback date.
        $copy = clone $entry;
        $copy->data($this->originalData($entry));
        $copy->date(null);

        $format = 'Y-m-d';

        if ($copy->hasTime()) {
            $format .= '-Hi';
        }

        if ($copy->hasSeconds()) {
            $format .= 's';
        }

        return $copy->date()->copy()->setTimezone(config('app.timezone'))->format($format);
    }

    /**
     * Use the indexed URL captured while checking date changes. Otherwise
     * reconstruct the URL from the previous slug, date, and stored data.
     * Restoring the data preserves absent fields that inherit from an origin.
     * The copy shares the entry id, so flush its URI cache before and after.
     */
    private function stacheOriginalUrl(Entry $entry, EntryUrlValues $values): ?string
    {
        if ($url = Blink::get($this->urlBlinkKey($entry))) {
            return $url;
        }

        if (! $entry->initialPath()) {
            return null;
        }

        $copy = clone $entry;

        if ($values->slug !== null) {
            $copy->slug($values->slug);
        }

        if ($values->date !== null) {
            $copy->date($values->date);
        }

        $copy->data($this->originalData($entry));

        Blink::store('entry-uris')->forget($copy->id());
        Blink::store('structure-uris')->forget($copy->id());

        $url = $copy->absoluteUrlWithoutRedirect();

        $this->restoreEntryAfterCopy($entry);

        return $url;
    }

    /**
     * URL copies share the entry id and structured Page. Restore the live entry
     * and clear the Page's route data before another route can reuse the copy.
     */
    private function restoreEntryAfterCopy(Entry $entry): void
    {
        $entry->page()?->setEntry($entry);
        Blink::store('entry-uris')->forget($entry->id());
        Blink::store('structure-uris')->forget($entry->id());
    }

    /**
     * The indexed URL distinguishes repeated DST times that filenames cannot.
     * A rebuilt index may already contain the edited URI, so only retain a
     * different URL.
     */
    private function stacheIndexedOriginalUrl(Entry $entry): ?string
    {
        return Blink::once($this->urlBlinkKey($entry), function () use ($entry) {
            $uri = Stache::store('entries')->store($entry->collectionHandle())->index('uri')->get($entry->id());

            if (! $uri) {
                return null;
            }

            Blink::store('entry-uris')->forget($entry->id());
            Blink::store('structure-uris')->forget($entry->id());
            $currentUri = $entry->uri();
            Blink::store('entry-uris')->forget($entry->id());
            Blink::store('structure-uris')->forget($entry->id());

            if ($uri === $currentUri) {
                return null;
            }

            return str_starts_with($uri, '/')
                ? URL::assemble($entry->site()->absoluteUrl(), $uri)
                : $uri;
        });
    }

    /**
     * A file path takes precedence because custom file entries may also expose model().
     */
    private function isEloquentEntry(Entry $entry): bool
    {
        return ! $entry->initialPath() && method_exists($entry, 'model');
    }

    private function eloquentOriginalUrl(Entry $entry): ?string
    {
        $uri = $entry->model()?->getOriginal('uri');

        if (! $uri) {
            return null;
        }

        return str_starts_with($uri, '/')
            ? URL::assemble($entry->site()->absoluteUrl(), $uri)
            : $uri;
    }

    /**
     * The Eloquent date stored before this save. getOriginal('date') only keeps
     * the minute, so a value with seconds is read from the column.
     */
    private function eloquentOriginalDate(Entry $entry): ?string
    {
        $stored = $entry->model()->getOriginal('date');
        $date = $stored ? Carbon::parse($stored) : null;

        if ($date && ($date->second || $entry->hasSeconds())) {
            return $date->format('Y-m-d-His');
        }

        return $entry->getOriginal('date');
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
}
