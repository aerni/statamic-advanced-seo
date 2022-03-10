<?php

namespace Aerni\AdvancedSeo\Commands;

use Aerni\AdvancedSeo\Migrators\AardvarkSeoMigrator;
use Illuminate\Console\Command;
use Statamic\Console\RunsInPlease;

class Migrate extends Command
{
    use RunsInPlease;

    protected $signature = 'seo:migrate';
    protected $description = 'Migrate other SEO addons to Advanced SEO';

    public function handle(): void
    {
        $choice = $this->choice('Choose the addon you are migrating from', array_keys($this->addons()));

        resolve($this->addons()[$choice])::run();

        $this->line("<info>[✓]</info> The migration has been successful!");
    }

    protected function addons(): array
    {
        return [
            'Aardvark SEO' => AardvarkSeoMigrator::class,
        ];
    }
}
