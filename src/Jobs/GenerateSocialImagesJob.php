<?php

namespace Aerni\AdvancedSeo\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class GenerateSocialImagesJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(protected Collection $items)
    {
        $this->queue = config('advanced-seo.social_images.generator.queue');
    }

    public function handle()
    {
        $this->items->each(function ($item) {
            GenerateSocialImageJob::dispatch($item);
        });
    }
}
