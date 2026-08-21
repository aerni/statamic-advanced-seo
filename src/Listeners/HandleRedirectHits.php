<?php

namespace Aerni\AdvancedSeo\Listeners;

use Aerni\AdvancedSeo\Events\RedirectDeleted;
use Aerni\AdvancedSeo\Facades\Redirect;

class HandleRedirectHits
{
    public function handleRedirectDeleted(RedirectDeleted $event): void
    {
        Redirect::hits()->find($event->redirect->id())?->delete();
    }
}
