<?php

namespace Aerni\AdvancedSeo\Redirects;

use Aerni\AdvancedSeo\Contracts\Redirect;
use Aerni\AdvancedSeo\Facades\Redirect as RedirectFacade;
use Statamic\Facades\Site;

class RedirectErrorInbox
{
    public function deleteErrorsHandledBy(Redirect $redirect): void
    {
        if (! $redirect->enabled()) {
            return;
        }

        if (! $redirect->resolves()) {
            return;
        }

        RedirectFacade::errors()->query()
            ->where('site', $redirect->site())
            ->get()
            ->filter(fn ($error) => RedirectPatternMatcher::matches($redirect->source(), $error->url()))
            ->each->delete();
    }

    public function deleteHandledErrors(): void
    {
        RedirectFacade::query()
            ->where('enabled', true)
            ->whereIn('site', Site::all()->map->handle()->all())
            ->get()
            ->each(fn ($redirect) => $this->deleteErrorsHandledBy($redirect));
    }
}
