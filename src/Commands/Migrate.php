<?php

namespace Aerni\AdvancedSeo\Commands;

use Aerni\AdvancedSeo\Migrators\AardvarkSeoMigrator;
use Aerni\AdvancedSeo\Migrators\SeoProMigrator;
use Illuminate\Console\Command;
use Statamic\Console\RunsInPlease;

use function Laravel\Prompts\select;

class Migrate extends Command
{
    use RunsInPlease;

    protected $signature = 'seo:migrate {migrator? : The migrator to run (aardvark-seo, seo-pro)}';

    protected $description = 'Migrate your existing content';

    public function handle(): void
    {
        $migrators = $this->migrators();

        $key = $this->argument('migrator') ?? select(
            label: 'Which addon are you migrating from?',
            options: array_combine(array_keys($migrators), array_column($migrators, 'label')),
        );

        resolve($migrators[$key]['class'])::run();

        $this->line('<info>[✓]</info> The migration has been successful!');
    }

    protected function migrators(): array
    {
        return [
            'aardvark-seo' => ['label' => 'Aardvark SEO', 'class' => AardvarkSeoMigrator::class],
            'seo-pro' => ['label' => 'SEO Pro', 'class' => SeoProMigrator::class],
        ];
    }
}
