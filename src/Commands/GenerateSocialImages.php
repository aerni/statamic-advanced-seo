<?php

namespace Aerni\AdvancedSeo\Commands;

use Aerni\AdvancedSeo\Context\Context;
use Aerni\AdvancedSeo\Features\SocialImagesGenerator;
use Aerni\AdvancedSeo\Jobs\GenerateSocialImagesJob;
use Illuminate\Console\Command;

use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\progress;
use function Laravel\Prompts\warning;
use Statamic\Console\RunsInPlease;
use Statamic\Facades\Entry;

class GenerateSocialImages extends Command
{
    use RunsInPlease;

    protected $signature = 'seo:generate-images {--queue}';

    protected $description = 'Generate all social images';

    protected bool $shouldQueue = false;

    public function handle(): void
    {
        if (! SocialImagesGenerator::enabled()) {
            error('The social images feature is disabled. Enable it in config/advanced-seo.php.');

            return;
        }

        $entries = Entry::all()->filter(fn ($entry) => SocialImagesGenerator::enabled(Context::from($entry)));

        if ($entries->isEmpty()) {
            info('There are no images to generate.');

            return;
        }

        $this->shouldQueue = $this->option('queue');

        if ($this->shouldQueue && config('queue.default') === 'sync') {
            warning('The queue connection is set to "sync". Queueing will be disabled.');
            $this->shouldQueue = false;
        }

        $this->shouldQueue
            ? $entries->each(fn ($entry) => GenerateSocialImagesJob::dispatch($entry))
            : progress(
                label: 'Generating social images...',
                steps: $entries,
                callback: fn ($entry) => GenerateSocialImagesJob::dispatchSync($entry),
            );

        $this->shouldQueue
            ? info('The social images have been queued for generation.')
            : info('The social images have been successfully generated.');
    }
}
