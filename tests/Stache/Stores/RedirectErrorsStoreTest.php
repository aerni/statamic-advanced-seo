<?php

use Aerni\AdvancedSeo\Contracts\RedirectError;
use Aerni\AdvancedSeo\Stache\Stores\RedirectErrorsStore;
use Facades\Statamic\Stache\Traverser;
use Statamic\Stache\Stache;

beforeEach(function (): void {
    $this->files = app('files');
    $this->files->ensureDirectoryExists(tempPath());

    $this->store = (new RedirectErrorsStore(app(Stache::class), $this->files))->directory(tempPath());
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

    expect(tempPath('ignored.txt'))->toBeFile()
        ->and(tempPath('nested/def.yaml'))->toBeFile();
});

it('makes RedirectError instances from file', function (): void {
    $path = tempPath('abc.yaml');

    $this->files->put($path, <<<'YAML'
url: /missing
site: default
count: 3
first_seen_at: 1751450400
last_seen_at: 1751450500
YAML);

    $item = $this->store->makeItemFromFile($path, $this->files->get($path));

    expect($item)->toBeInstanceOf(RedirectError::class)
        ->and($item->id())->toBe('abc')
        ->and($item->url())->toBe('/missing')
        ->and($item->site())->toBe('default')
        ->and($item->count())->toBe(3)
        ->and($item->firstSeenAt())->toBe(1751450400)
        ->and($item->lastSeenAt())->toBe(1751450500);
});

it('hydrates yaml with a missing or null url', function (): void {
    $missing = tempPath('missing.yaml');
    $nullUrl = tempPath('null.yaml');

    $this->files->put($missing, <<<'YAML'
site: default
count: 1
YAML);

    $this->files->put($nullUrl, <<<'YAML'
url: ~
site: default
count: 1
YAML);

    expect($this->store->makeItemFromFile($missing, $this->files->get($missing))->url())->toBeNull()
        ->and($this->store->makeItemFromFile($nullUrl, $this->files->get($nullUrl))->url())->toBeNull();
});
