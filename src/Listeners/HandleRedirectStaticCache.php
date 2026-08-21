<?php

namespace Aerni\AdvancedSeo\Listeners;

use Aerni\AdvancedSeo\Contracts\Redirect;
use Aerni\AdvancedSeo\Enums\RedirectSourceType;
use Aerni\AdvancedSeo\Events\RedirectSaved;
use Aerni\AdvancedSeo\Redirects\RedirectPatternMatcher;
use Statamic\Facades\Site;
use Statamic\Facades\URL;
use Statamic\StaticCaching\Cacher;
use Statamic\StaticCaching\Cachers\NullCacher;

class HandleRedirectStaticCache
{
    public function __construct(protected Cacher $cacher) {}

    public function handleRedirectSaved(RedirectSaved $event): void
    {
        $this->invalidate($event->redirect);
    }

    protected function invalidate(Redirect $redirect): void
    {
        if ($this->cacher instanceof NullCacher) {
            return;
        }

        $urls = $this->urls($redirect);

        if ($urls === []) {
            return;
        }

        $this->cacher->invalidateUrls($urls);
    }

    /**
     * @return array<int, string>
     */
    protected function urls(Redirect $redirect): array
    {
        $site = Site::get($redirect->site());

        if ($redirect->sourceType() === RedirectSourceType::Exact) {
            return [$redirect->sourceUrl()];
        }

        return $this->cacher->getDomains()
            ->flatMap(fn ($domain) => $this->cacher->getUrls($domain)->map(
                fn (string $url) => URL::assemble($domain, $url)
            ))
            ->filter(function (string $absoluteUrl) use ($redirect, $site) {
                $path = RedirectPatternMatcher::normalizePath($site->relativePath($absoluteUrl));

                return RedirectPatternMatcher::matches($redirect->source(), $path);
            })
            ->values()
            ->all();
    }
}
