<?php

namespace Aerni\AdvancedSeo\Listeners;

use Aerni\AdvancedSeo\Events\RedirectSaved;
use Aerni\AdvancedSeo\Redirects\RedirectErrorInbox;

class HandleRedirectErrors
{
    public function handleRedirectSaved(RedirectSaved $event): void
    {
        app(RedirectErrorInbox::class)->deleteErrorsHandledBy($event->redirect);
    }
}
