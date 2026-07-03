<?php

namespace Aerni\AdvancedSeo\Listeners;

use Aerni\AdvancedSeo\Events\RedirectDeleted;
use Aerni\AdvancedSeo\Facades\Redirects;

class HandleRedirectHits
{
    public function handleRedirectDeleted(RedirectDeleted $event): void
    {
        Redirects::hits()->find($event->redirect->id())?->delete();
    }
}
