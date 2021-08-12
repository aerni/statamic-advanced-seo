<?php

namespace Aerni\AdvancedSeo\Listeners;

use Aerni\AdvancedSeo\Facades\SocialImage;
use Statamic\Events\EntrySaved;
use Illuminate\Contracts\Queue\ShouldQueue;

class GenerateSocialImage implements ShouldQueue
{
    public function handle(EntrySaved $event): void
    {
        if (SocialImage::shouldGenerate('og', $event->entry->id())) {
            SocialImage::make('og', $event->entry->id());
        }

        if (SocialImage::shouldGenerate('twitter', $event->entry->id())) {
            SocialImage::make('twitter', $event->entry->id());
        }
    }

    public function viaQueue(): string
    {
        return config('advanced-seo.social_images.generator.queue', 'default');
    }
}
