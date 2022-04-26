<?php

namespace Aerni\AdvancedSeo\Commands;

use Aerni\AdvancedSeo\Actions\ShouldGenerateSocialImages;
use Aerni\AdvancedSeo\Jobs\GenerateSocialImagesJob;
use Illuminate\Console\Command;
use Statamic\Console\RunsInPlease;
use Statamic\Facades\Entry;

class GenerateSocialImages extends Command
{
    use RunsInPlease;

    protected $signature = 'seo:generate-images';
    protected $description = 'Generate all your social images';

    public function handle(): void
    {
        $entries = Entry::all()->filter(fn ($entry) => ShouldGenerateSocialImages::handle($entry));

        if ($entries->isEmpty()) {
            $this->info("There are no images to generate");

            return;
        }

        if (config('queue.default') === 'sync') {
            $this->info("Generating social images ...");
            $this->withProgressBar($entries, fn ($entry) => GenerateSocialImagesJob::dispatch($entry));
            $this->newLine();
            $this->info("<info>[✓]</info> The social images have been succesfully generated");

            return;
        }

        $entries->each(fn ($entry) => GenerateSocialImagesJob::dispatch($entry));
        $this->info("The social images are being generated in the background");
    }
}
