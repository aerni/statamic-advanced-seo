<?php

namespace Aerni\AdvancedSeo\Events;

use Statamic\Events\Event;
use Aerni\AdvancedSeo\Data\SeoDefaultSet;
use Statamic\Contracts\Git\ProvidesCommitMessage;

class SeoDefaultSetSaved extends Event implements ProvidesCommitMessage
{
    public function __construct(public SeoDefaultSet $defaults)
    {
        //
    }

    public function commitMessage()
    {
        return __('SEO defaults saved', [], config('statamic.git.locale'));
    }
}
