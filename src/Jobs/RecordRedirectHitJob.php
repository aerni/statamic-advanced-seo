<?php

namespace Aerni\AdvancedSeo\Jobs;

use Aerni\AdvancedSeo\Facades\Redirect;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

class RecordRedirectHitJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    public function __construct(public string $redirect)
    {
        $this->queue = config('advanced-seo.redirects.queue', 'default');
    }

    public function handle(): void
    {
        Redirect::hits()->record($this->redirect);
    }
}
