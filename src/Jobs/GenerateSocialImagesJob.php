<?php

namespace Aerni\AdvancedSeo\Jobs;

use Illuminate\Bus\Queueable;
use Statamic\Contracts\Entries\Entry;
use Illuminate\Queue\InteractsWithQueue;
use Aerni\AdvancedSeo\Facades\SocialImage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Aerni\AdvancedSeo\Actions\ClearImageGlideCache;

class GenerateSocialImagesJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    public function __construct(protected Entry $entry)
    {
        $this->queue = config('advanced-seo.social_images.generator.queue', 'default');
    }

    public function handle(): void
    {
        SocialImage::all($this->entry)
            ->each(fn ($image) => $image->generate())
            ->each(fn ($image) => ClearImageGlideCache::handle($image->path()));
    }
}
