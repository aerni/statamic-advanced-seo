<?php

use Aerni\AdvancedSeo\Contracts\SeoSetConfig;
use Aerni\AdvancedSeo\Stache\Stores\SeoSetConfigsStore;
use Facades\Statamic\Stache\Traverser;
use Statamic\Facades\Collection;
use Statamic\Facades\Site;
use Statamic\Facades\Taxonomy;
use Statamic\Stache\Stache;

beforeEach(function (): void {
    $this->files = app('files');
    $this->files->ensureDirectoryExists(tempPath());

    Site::setSites([
        'english' => ['name' => 'English', 'url' => '/', 'locale' => 'en'],
        'german' => ['name' => 'German', 'url' => '/de', 'locale' => 'de'],
    ]);

    $stache = (new Stache)->sites(['english', 'german']);
    $this->app->instance(Stache::class, $stache);

    $this->store = (new SeoSetConfigsStore($stache, $this->files))->directory(tempPath());
    $stache->registerStore($this->store);

    $collection = (new \Statamic\Entries\Collection)
        ->handle('articles')
        ->title('Articles')
        ->sites(['english', 'german']);

    Collection::shouldReceive('find')->with('articles')->andReturn($collection);
    Collection::shouldReceive('all')->andReturn(collect([$collection]));
    Taxonomy::shouldReceive('all')->andReturn(collect());
});

afterEach(function (): void {
    $this->files->deleteDirectory(tempPath());
});

it('filters yaml files one level deep', function (): void {
    $this->files->put(tempPath('root.yaml'), '');

    $this->files->ensureDirectoryExists(tempPath('collections/en'));
    $this->files->put(tempPath('collections/articles.yaml'), '');
    $this->files->put(tempPath('collections/ignored.txt'), '');
    $this->files->put(tempPath('collections/en/ignored.yaml'), '');

    $this->files->ensureDirectoryExists(tempPath('taxonomies'));
    $this->files->put(tempPath('taxonomies/categories.yaml'), '');

    $files = Traverser::filter([$this->store, 'getItemFilter'])->traverse($this->store);

    expect($files->keys()->all())->toMatchArray([
        tempPath('collections/articles.yaml'),
        tempPath('taxonomies/categories.yaml'),
    ]);

    /* Sanity check. Make sure the files are there but were not included. */
    expect(tempPath('root.yaml'))->toBeFile()
        ->and(tempPath('collections/ignored.txt'))->toBeFile()
        ->and(tempPath('collections/en/ignored.yaml'))->toBeFile();
});

it('makes SeoSetConfig instances from file', function (): void {
    $path = tempPath('collections/articles.yaml');

    $this->files->ensureDirectoryExists(tempPath('collections'));

    $this->files->put($path, <<<'YAML'
enabled: false
origins:
  english:
  german: english
sitemap: true
YAML);

    $item = $this->store->makeItemFromFile($path, $this->files->get($path));

    expect($item)->toBeInstanceOf(SeoSetConfig::class)
        ->and($item->initialPath())->toBe($path)
        ->and($item->id())->toBe('collections::articles')
        ->and($item->enabled())->toBeFalse()
        ->and($item->origins()->all())->toBe([
            'english' => null,
            'german' => 'english',
        ])
        ->and($item->data()->all())->toBe([
            'sitemap' => true,
        ]);
});
