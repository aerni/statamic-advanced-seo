<?php

namespace Aerni\AdvancedSeo\Jobs;

use Aerni\AdvancedSeo\Facades\Redirect;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Cache\LockTimeoutException;
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

    public int $tries = 3;

    public function __construct(public string $url, public string $site)
    {
        $this->queue = config('advanced-seo.redirects.queue', 'default');
    }

    public function uniqueId(): string
    {
        return "{$this->site}:{$this->url}";
    }

    /**
     * Recording a 404 is best-effort. Different paths share one write lock, so a
     * scanner burst can hold it longer than this job is willing to wait. Try
     * again a couple of times, then stop without failing. A real 404 in that
     * burst can be missed until a later request.
     */
    public function handle(): void
    {
        try {
            Redirect::errors()->record($this->url, $this->site);
        } catch (LockTimeoutException) {
            if ($this->attempts() >= $this->tries) {
                return;
            }

            // release() retries this same job. Dispatching a new one is dropped
            // while the ShouldBeUnique lock from the original dispatch is held.
            $this->release(random_int(5, 15));
        }
    }
}
