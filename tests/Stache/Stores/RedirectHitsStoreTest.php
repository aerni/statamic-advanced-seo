<?php

use Aerni\AdvancedSeo\Contracts\RedirectHit;
use Aerni\AdvancedSeo\Stache\Stores\RedirectHitsStore;
use Facades\Statamic\Stache\Traverser;
use Statamic\Stache\Stache;

beforeEach(function (): void {
    $this->files = app('files');
    $this->files->ensureDirectoryExists(tempPath());

    $this->store = (new RedirectHitsStore(app(Stache::class), $this->files))->directory(tempPath());
});

afterEach(function (): void {
    $this->files->deleteDirectory(tempPath());
});

it('includes only flat yaml files', function (): void {
    $this->files->put(tempPath('abc.yaml'), '');
    $this->files->put(tempPath('ignored.txt'), '');

    $this->files->ensureDirectoryExists(tempPath('nested'));
    $this->files->put(tempPath('nested/def.yaml'), '');

    $files = Traverser::filter([$this->store, 'getItemFilter'])->traverse($this->store);

    expect($files->keys()->all())->toBe([
        tempPath('abc.yaml'),
    ]);

    /* Sanity check. Make sure the excluded files are there but were not included. */
    expect(tempPath('ignored.txt'))->toBeFile()
        ->and(tempPath('nested/def.yaml'))->toBeFile();
});

it('makes RedirectHit instances from file', function (): void {
    $path = tempPath('abc.yaml');

    $this->files->put($path, <<<'YAML'
count: 7
last_hit_at: 1751450400
YAML);

    $item = $this->store->makeItemFromFile($path, $this->files->get($path));

    expect($item)->toBeInstanceOf(RedirectHit::class)
        ->and($item->redirect())->toBe('abc')
        ->and($item->id())->toBe('abc')
        ->and($item->count())->toBe(7)
        ->and($item->lastHitAt())->toBe(1751450400);
});
