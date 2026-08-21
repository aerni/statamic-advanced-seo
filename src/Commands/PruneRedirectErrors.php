<?php

namespace Aerni\AdvancedSeo\Commands;

use Aerni\AdvancedSeo\Facades\Redirect;
use Aerni\AdvancedSeo\Redirects\RedirectErrorInbox;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Statamic\Console\RunsInPlease;

class PruneRedirectErrors extends Command
{
    use RunsInPlease;

    protected $signature = 'seo:prune-redirect-errors';

    protected $description = 'Prune stored redirect errors past retention or over the record cap';

    public function handle(): int
    {
        $this->purgeExpiredRecords();
        $this->deleteHandledRecords();
        $this->enforceMaxRecords();

        return self::SUCCESS;
    }

    protected function purgeExpiredRecords(): void
    {
        $days = Redirect::errors()->purgeAfterDays();

        if ($days === null) {
            return;
        }

        $cutoff = Carbon::now()->subDays($days)->timestamp;

        Redirect::errors()->query()
            ->where('last_seen_at', '<', $cutoff)
            ->get()
            ->each->delete();
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

        Redirect::errors()->query()
            ->orderBy('count')
            ->orderBy('last_seen_at')
            ->limit($count - $max)
            ->get()
            ->each->delete();
    }
}
