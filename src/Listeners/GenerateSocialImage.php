<?php

namespace Aerni\AdvancedSeo\Listeners;

use Aerni\AdvancedSeo\Facades\SocialImage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Statamic\Events\EntrySaved;

class GenerateSocialImage implements ShouldQueue
{
    public function handle(EntrySaved $event): void
    {
        if (SocialImage::shouldGenerate($event->entry)) {
            SocialImage::make($event->entry);
        }
    }

    public function viaQueue(): string
    {
        return config('advanced-seo.social_images.generator.queue', 'default');
    }
}
