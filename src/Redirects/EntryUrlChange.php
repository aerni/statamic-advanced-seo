<?php

namespace Aerni\AdvancedSeo\Redirects;

use Carbon\Carbon;
use Statamic\Contracts\Entries\Entry;
use Statamic\Entries\GetDateFromPath;
use Statamic\Entries\GetSlugFromPath;
use Statamic\Facades\Antlers;
use Statamic\Facades\Blink;
use Statamic\Facades\URL;
use Statamic\Facades\YAML;

class EntryUrlChange
{
    /**
     * Previous values that differ from the entry now.
     * Route fields include tokens used by a localization route.
     * Empty when none of those values changed.
     */
    public function changedValues(Entry $entry): EntryUrlValues
    {
        // The entry, its child pages, and its localizations each ask during this save.
        return Blink::once($this->changedBlinkKey($entry), function () use ($entry) {
            $original = $this->originalValues($entry);
            $current = $this->currentValues($entry, $original);

            $changed = $original->diff($current);

            // Statamic rewrites dependent URIs when the date snapshot changes,
            // even if both dates format identically during a repeated DST hour.
            if ($original->date !== null && $changed->date === null && $this->isEloquentEntry($entry) && $entry->isDirty('date')) {
                return new EntryUrlValues(
                    slug: $changed->slug,
                    date: $original->date,
                    data: $changed->data,
                );
            }

            return $changed;
        });
    }

    public function forget(Entry $entry): void
    {
        Blink::forget($this->changedBlinkKey($entry));
        Blink::forget($this->dataBlinkKey($entry));
    }

    /**
     * Stache: the file still contains the old data; getOriginal() has already
     * been replaced. Eloquent: there is no file, so getOriginal() still contains
     * the pre-save data. Reuse the snapshot until this entry's save has been stashed.
     *
     * @return array<string, mixed>
     */
    public function originalData(Entry $entry): array
    {
        return Blink::once($this->dataBlinkKey($entry), fn () => ($path = $entry->initialPath())
            ? YAML::file($path)->parse()
            : $entry->getOriginal());
    }

    /**
     * The date stored before this save.
     */
    public function originalDate(Entry $entry): ?string
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
     * Eloquent keeps the previous URI in its model. Read it even when date
     * values format identically during a repeated DST hour. For file entries,
     * let routing compute the URL when its route values changed.
     * The previous slug, date, and stored data are applied to a copy so the
     * entry Statamic is about to save is left untouched. Restoring the data
     * also preserves absent fields, which inherit rather than store null.
     * The cached uri has to be flushed: the copy shares the entry id.
     */
    public function originalUrl(Entry $entry, EntryUrlValues $values): ?string
    {
        if ($this->isEloquentEntry($entry)) {
            return $this->eloquentOriginalUrl($entry);
        }

        if ($values->isEmpty()) {
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

        $url = $copy->absoluteUrlWithoutRedirect();

        Blink::store('entry-uris')->forget($entry->id());

        return $url;
    }

    /**
     * True when a changed route field or date is absent on the localization,
     * so it uses the origin's value. A route field only counts when this
     * localization's route uses it.
     */
    public function inheritsChangedValues(Entry $origin, Entry $localization): bool
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

                return [$token => $this->inheritedRouteValue($entry, $token)];
            })
            ->all();
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
    public function inheritsDate(Entry $origin, Entry $localization): bool
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
