<?php

namespace Aerni\AdvancedSeo\Events;

use Aerni\AdvancedSeo\Contracts\Redirect;
use Statamic\Contracts\Git\ProvidesCommitMessage;
use Statamic\Events\Event;

class RedirectDeleted extends Event implements ProvidesCommitMessage
{
    public function __construct(public Redirect $redirect) {}

    public function commitMessage()
    {
        return __('advanced-seo::messages.redirect_deleted', [], config('statamic.git.locale'));
    }
}
