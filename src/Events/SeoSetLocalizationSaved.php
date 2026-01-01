<?php

namespace Aerni\AdvancedSeo\Events;

use Aerni\AdvancedSeo\Contracts\SeoSetLocalization;
use Statamic\Contracts\Git\ProvidesCommitMessage;
use Statamic\Events\Event;

class SeoSetLocalizationSaved extends Event implements ProvidesCommitMessage
{
    public function __construct(public SeoSetLocalization $localization)
    {
        //
    }

    public function commitMessage()
    {
        return __('advanced-seo::messages.commit_message', [], config('statamic.git.locale'));
    }
}
