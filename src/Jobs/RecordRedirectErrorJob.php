<?php

namespace Aerni\AdvancedSeo\Jobs;

use Aerni\AdvancedSeo\Facades\Redirect;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

class RecordRedirectErrorJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    public int $uniqueFor = 60;

    public function __construct(public string $url, public string $site)
    {
        $this->queue = config('advanced-seo.redirects.queue', 'default');
    }

    public function uniqueId(): string
    {
        return "{$this->site}:{$this->url}";
    }

    public function handle(): void
    {
        Redirect::errors()->record($this->url, $this->site);
    }
}
