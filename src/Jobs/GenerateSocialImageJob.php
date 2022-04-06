<?php

namespace Aerni\AdvancedSeo\Jobs;

use Aerni\AdvancedSeo\Facades\SocialImage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Statamic\Contracts\Entries\Entry;

class GenerateSocialImageJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    // TODO: Have to make this work with Taxonomies as well.
    public function __construct(protected Entry $entry)
    {
        $this->queue = config('advanced-seo.social_images.generator.queue', config('queue.default'));
    }

    public function handle(): void
    {
        SocialImage::all($this->entry)->each(fn ($image) => $image->generate());
    }
}
