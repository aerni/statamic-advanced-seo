<?php

namespace Aerni\AdvancedSeo\Jobs;

use Aerni\AdvancedSeo\Facades\SocialImage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Statamic\Contracts\Entries\Entry;
use Statamic\Contracts\Taxonomies\Term;

class GenerateSocialImagesJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    public function __construct(protected Entry|Term $content)
    {
        $this->queue = config('advanced-seo.social_images.queue', 'default');
    }

    public function handle(): void
    {
        $asset = SocialImage::openGraph()
            ->for($this->content)
            ->generate();

        $this->content->set('seo_og_image', $asset->path());
        $this->content->saveQuietly();
    }
}
