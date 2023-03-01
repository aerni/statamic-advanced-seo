<?php

namespace Aerni\AdvancedSeo\Commands;

use Aerni\AdvancedSeo\Migrators\AardvarkSeoMigrator;
use Aerni\AdvancedSeo\Migrators\SeoProMigrator;
use Illuminate\Console\Command;
use Statamic\Console\RunsInPlease;

class Migrate extends Command
{
    use RunsInPlease;

    protected $signature = 'seo:migrate';
    protected $description = 'Migrate your existing content';

    public function handle(): void
    {
        $choice = $this->choice('Choose your migration', array_keys($this->migrations()));

        resolve($this->migrations()[$choice])::run();

        $this->line('<info>[✓]</info> The migration has been successful!');
    }

    protected function migrations(): array
    {
        return [
            'Aardvark SEO' => AardvarkSeoMigrator::class,
            'SEO Pro' => SeoProMigrator::class,
        ];
    }
}
