<?php

namespace Aerni\AdvancedSeo\Redirects;

use Aerni\AdvancedSeo\Contracts\Redirect;
use Aerni\AdvancedSeo\Facades\Redirect as RedirectFacade;
use Statamic\Facades\Site;

class RedirectErrorInbox
{
    public function deleteErrorsHandledBy(Redirect $redirect): void
    {
        RedirectFacade::errors()->deleteByIds($this->errorsHandledBy($redirect));
    }

    public function deleteHandledErrors(): void
    {
        $ids = RedirectFacade::query()
            ->where('enabled', true)
            ->whereIn('site', Site::all()->map->handle()->all())
            ->get()
            ->flatMap(fn ($redirect) => $this->errorsHandledBy($redirect))
            ->unique()
            ->values()
            ->all();

        RedirectFacade::errors()->deleteByIds($ids);
    }

    protected function errorsHandledBy(Redirect $redirect): array
    {
        if (! $redirect->enabled()) {
            return [];
        }

        if (! $redirect->resolves()) {
            return [];
        }

        return RedirectFacade::errors()->query()
            ->where('site', $redirect->site())
            ->get()
            ->filter(function ($error) use ($redirect) {
                $url = $error->url();

                return is_string($url) && $url !== ''
                    && RedirectPatternMatcher::matches($redirect->source(), $url);
            })
            ->map->id()
            ->all();
    }
}
