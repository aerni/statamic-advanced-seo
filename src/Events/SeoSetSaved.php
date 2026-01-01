<?php

namespace Aerni\AdvancedSeo\Events;

use Aerni\AdvancedSeo\Data\SeoSet;
use Statamic\Contracts\Git\ProvidesCommitMessage;
use Statamic\Events\Event;

class SeoSetSaved extends Event implements ProvidesCommitMessage
{
    public function __construct(public SeoSet $default)
    {
        //
    }

    public function commitMessage()
    {
        return __('advanced-seo::messages.commit_message', [], config('statamic.git.locale'));
    }
}
