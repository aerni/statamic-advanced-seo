<?php

namespace Aerni\AdvancedSeo\Commands;

use Illuminate\Console\Command;
use Statamic\Console\RunsInPlease;
use Aerni\AdvancedSeo\Migrators\SeoProMigrator;
use Aerni\AdvancedSeo\Migrators\AardvarkSeoMigrator;

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
            'SEO Pro' => SeoProMigrator::class,
        ];
    }
}
