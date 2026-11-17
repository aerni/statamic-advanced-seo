<?php

namespace Aerni\AdvancedSeo\Commands;

use Aerni\AdvancedSeo\Facades\Redirect;
use Aerni\AdvancedSeo\Redirects\RedirectErrorInbox;
use Illuminate\Console\Command;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Statamic\Console\RunsInPlease;

class PruneRedirectErrors extends Command
{
    use RunsInPlease;

    /**
     * Each try waits out the shared write lock, which blocks for 5 seconds.
     * The attempt ceiling stops the loop if that wait does not move the clock.
     */
    private const LOCK_ATTEMPTS = 15;

    private const LOCK_BUDGET_SECONDS = 60;

    protected $signature = 'seo:prune-redirect-errors';

    protected $description = 'Prune stored redirect errors past retention or over the record cap';

    /**
     * Pruning is best-effort. The same lock guards record jobs, which only
     * wait a few seconds, so a burst can still be holding it when this
     * nightly run starts. Wait about a minute, then stop without failing
     * the scheduler. Records already deleted stay deleted.
     */
    public function handle(): int
    {
        try {
            $this->prune();
        } catch (LockTimeoutException) {
            $message = 'Redirect error pruning did not finish because the write lock stayed busy. It will run again on the next schedule.';

            Log::warning($message);

            $this->warn($message);
        }

        return self::SUCCESS;
    }

    protected function prune(): void
    {
        $deadline = now()->addSeconds(self::LOCK_BUDGET_SECONDS);

        for ($attempt = 1; $attempt <= self::LOCK_ATTEMPTS; $attempt++) {
            try {
                $this->purgeExpiredRecords();
                $this->deleteHandledRecords();
                $this->enforceMaxRecords();

                return;
            } catch (LockTimeoutException $e) {
                if ($attempt === self::LOCK_ATTEMPTS || now()->greaterThanOrEqualTo($deadline)) {
                    throw $e;
                }
            }
        }
    }

    protected function purgeExpiredRecords(): void
    {
        $days = Redirect::errors()->purgeAfterDays();

        if ($days === null) {
            return;
        }

        $cutoff = Carbon::now()->subDays($days)->timestamp;

        $ids = Redirect::errors()->query()
            ->where('last_seen_at', '<', $cutoff)
            ->get()
            ->map->id()
            ->all();

        Redirect::errors()->deleteByIds($ids);
    }

    protected function deleteHandledRecords(): void
    {
        app(RedirectErrorInbox::class)->deleteHandledErrors();
    }

    protected function enforceMaxRecords(): void
    {
        $max = Redirect::errors()->maxRecords();

        if ($max === null) {
            return;
        }

        $count = Redirect::errors()->query()->count();

        if ($count <= $max) {
            return;
        }

        $ids = Redirect::errors()->query()
            ->orderBy('count')
            ->orderBy('last_seen_at')
            ->limit($count - $max)
            ->get()
            ->map->id()
            ->all();

        Redirect::errors()->deleteByIds($ids);
    }
}
