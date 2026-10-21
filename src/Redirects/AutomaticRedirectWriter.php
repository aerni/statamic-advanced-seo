<?php

namespace Aerni\AdvancedSeo\Redirects;

use Aerni\AdvancedSeo\Contracts\Redirect;
use Aerni\AdvancedSeo\Enums\RedirectOrigin;
use Aerni\AdvancedSeo\Enums\RedirectResponseCode;
use Aerni\AdvancedSeo\Facades\Redirect as RedirectFacade;
use Statamic\Facades\Entry as EntryFacade;
use Statamic\Facades\Term as TermFacade;

class AutomaticRedirectWriter
{
    /**
     * Delete auto-created redirects whose source is this URL. The URL now
     * belongs to a live page, so the redirect would send the request away.
     * Manual redirects are the editor's to keep.
     */
    public function deleteBySource(string $source, string $site): void
    {
        RedirectFacade::query()
            ->where('site', $site)
            ->whereSource($source)
            ->where('origin', RedirectOrigin::Automatic->value)
            ->get()
            ->each(fn (Redirect $redirect) => $redirect->delete());
    }

    /**
     * Create the redirect from the old to the new URL. An existing redirect
     * with the same source is a deliberate editor decision and wins. A source
     * still owned by published content must remain routable and is left alone.
     * An automatic redirect whose destination is the old path is pointed
     * straight at the new one, including when the source was left to an
     * existing redirect. Manual redirects are left alone.
     */
    public function create(string $source, string $destination, string $site): void
    {
        if ($this->sourceIsOwnedByPublishedContent($source, $site)) {
            return;
        }

        $existing = RedirectFacade::query()
            ->where('site', $site)
            ->whereSource($source)
            ->first();

        if (! $existing) {
            RedirectFacade::make()
                ->source($source)
                ->destination($destination)
                ->responseCode(RedirectResponseCode::Permanent)
                ->site($site)
                ->origin(RedirectOrigin::Automatic)
                ->save();
        }

        $this->replaceDestination($source, $destination, $site);
    }

    /**
     * Delete the auto-created redirects that point at a given destination,
     * used to clear redirects left dangling by a deleted entry or term.
     */
    public function deleteByDestination(string $destination, ?string $site = null): void
    {
        RedirectFacade::query()
            ->when($site, fn ($query) => $query->where('site', $site))
            ->where('destination', $destination)
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

    /**
     * Only a published term that Statamic can serve owns its URL.
     */
    private function sourceIsOwnedByPublishedTerm(string $source, string $site): bool
    {
        $term = TermFacade::findByUri($source, $site);

        if (! $term) {
            return false;
        }

        if ($term->status() !== 'published') {
            return false;
        }

        if (! view()->exists($term->template())) {
            return false;
        }

        return ! $term->collection() || $term->taxonomy()->collections()->contains($term->collection());
    }

    /**
     * An auto-created redirect whose destination is the old path is now stale:
     * following it would take two hops (A → old → new). Point it straight at the
     * new destination.
     */
    private function replaceDestination(string $from, string $to, string $site): void
    {
        RedirectFacade::query()
            ->where('site', $site)
            ->where('destination', $from)
            ->where('origin', RedirectOrigin::Automatic->value)
            ->get()
            ->each(fn (Redirect $redirect) => $redirect->destination($to)->save());
    }
}
