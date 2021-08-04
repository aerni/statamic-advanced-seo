<?php

namespace Aerni\AdvancedSeo\Commands;

use Statamic\Facades\Site;
use Illuminate\Console\Command;
use Statamic\Facades\GlobalSet;
use Aerni\AdvancedSeo\Facades\SeoGlobals;
use Statamic\Console\RunsInPlease;
use Statamic\Facades\AssetContainer;

class SetupSeo extends Command
{
    use RunsInPlease;

    protected $signature = 'seo:setup';
    protected $description = 'Setup Advanced SEO';

    public function handle(): void
    {
        $this->setupGlobals();
        $this->setupAssetContainers();

        $this->info("Advanced SEO is configured and ready to go!");
    }

    protected function setupGlobals(): void
    {
        $this->makeGlobal(SeoGlobals::handle(), SeoGlobals::title());
    }

    protected function setupAssetContainers(): void
    {
        $this->makeAssetContainer('seo', 'SEO');
    }

    protected function makeGlobal(string $handle, string $title): void
    {
        if (! GlobalSet::findByHandle($handle)) {
            $global = GlobalSet::make($handle)->title($title);

            $global->addLocalization($global->makeLocalization(Site::default()->handle()));

            $global->save();

            $this->line("<info>[✓]</info> Created SEO global in <comment>content/globals/$handle.yaml</comment>");
        }
    }

    protected function makeAssetContainer(string $handle, string $title): void
    {
        if (! AssetContainer::findByHandle($handle)) {
            AssetContainer::make($handle)
                ->title($title)
                ->disk('assets')
                ->allowUploads(true)
                ->allowDownloading(true)
                ->allowMoving(true)
                ->allowRenaming(true)
                ->createFolders(true)
                ->save();

            $this->line("<info>[✓]</info> Created $title assets container in <comment>content/assets/$handle.yaml</comment>");
        }
    }
}
