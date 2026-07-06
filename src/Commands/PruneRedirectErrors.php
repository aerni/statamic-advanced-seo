<?php

namespace Aerni\AdvancedSeo\Commands;

use Aerni\AdvancedSeo\Facades\Redirect;
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
        $this->enforceMaxRecords();

        return self::SUCCESS;
    }

    protected function purgeExpiredRecords(): void
    {
        $days = (int) config('advanced-seo.redirects.errors.purge_after_days', 30);

        $cutoff = Carbon::now()->subDays($days)->timestamp;

        Redirect::errors()->query()
            ->where('last_seen_at', '<', $cutoff)
            ->get()
            ->each->delete();
    }

    protected function enforceMaxRecords(): void
    {
        $max = max(1, (int) config('advanced-seo.redirects.errors.max_records', 1000));

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
