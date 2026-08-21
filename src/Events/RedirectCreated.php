<?php

namespace Aerni\AdvancedSeo\Events;

use Aerni\AdvancedSeo\Contracts\Redirect;
use Statamic\Contracts\Git\ProvidesCommitMessage;
use Statamic\Events\Event;

class RedirectCreated extends Event implements ProvidesCommitMessage
{
    public function __construct(public Redirect $redirect) {}

    public function commitMessage()
    {
        return __('advanced-seo::messages.redirect_created', [], config('statamic.git.locale'));
    }
}
