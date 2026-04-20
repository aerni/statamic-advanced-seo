<?php

namespace Aerni\AdvancedSeo\Events;

use Aerni\AdvancedSeo\Contracts\SeoSetConfig;
use Statamic\Contracts\Git\ProvidesCommitMessage;
use Statamic\Events\Event;

class SeoSetConfigDeleted extends Event implements ProvidesCommitMessage
{
    public function __construct(public SeoSetConfig $config)
    {
        //
    }

    public function commitMessage()
    {
        return __('advanced-seo::messages.seo_set_config_deleted', [], config('statamic.git.locale'));
    }
}
