<?php

namespace Aerni\AdvancedSeo\Tests\Concerns;

trait FakesComposerLock
{
    protected function installScreenshotPackage(): void
    {
        $this->fakeComposerLock('composer.screenshot.lock');
    }

    protected function uninstallScreenshotPackage(): void
    {
        $this->fakeComposerLock('composer.empty.lock');
    }

    protected function fakeComposerLock(string $fixture): void
    {
        copy(
            __DIR__.'/../__fixtures__/'.$fixture,
            __DIR__.'/../../vendor/orchestra/testbench-core/laravel/composer.lock',
        );
    }
}
