<?php

namespace Aerni\AdvancedSeo\Events;

use Aerni\AdvancedSeo\Contracts\SeoSetConfig;
use Statamic\Contracts\Git\ProvidesCommitMessage;
use Statamic\Events\Event;

class SeoSetConfigSaved extends Event implements ProvidesCommitMessage
{
    public function __construct(public SeoSetConfig $config)
    {
        //
    }

    public function commitMessage()
    {
        return __('advanced-seo::messages.commit_message', [], config('statamic.git.locale'));
    }
}
