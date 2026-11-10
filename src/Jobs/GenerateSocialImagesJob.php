<?php

namespace Aerni\AdvancedSeo\Jobs;

use Aerni\AdvancedSeo\Facades\SocialImage;
use Aerni\AdvancedSeo\Features\SocialImagesGenerator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Statamic\Contracts\Entries\Entry;
use Statamic\Contracts\Taxonomies\Term;

class GenerateSocialImagesJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    public function __construct(protected Entry|Term $content)
    {
        $this->queue = config('advanced-seo.social_images.generator.queue', 'default');
    }

    /**
     * A second save while a screenshot is still running must wait and then
     * generate. Dropping that job would keep the image from the first save.
     *
     * @return array<int, WithoutOverlapping>
     */
    public function middleware(): array
    {
        return [
            (new WithoutOverlapping($this->content->id().':'.$this->content->locale()))
                ->releaseAfter(15)
                ->expireAfter(180),
        ];
    }

    public function handle(): void
    {
        if (! SocialImagesGenerator::enabled()) {
            return;
        }

        $asset = SocialImage::openGraph()
            ->for($this->content)
            ->generate();

        $this->content->set('seo_og_image', $asset->path());
        $this->content->saveQuietly();
    }
}
