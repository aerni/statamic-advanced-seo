<?php

namespace Aerni\AdvancedSeo\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Collection;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class GenerateSocialImagesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private Collection $items;

    public function __construct(Collection $items)
    {
        $this->items = $items;
    }

    public function handle()
    {
        $this->items->each(function ($item) {
            GenerateSocialImageJob::dispatch($item);
        });
    }
}
