<?php

use Aerni\AdvancedSeo\Contracts\SeoSetLocalization as SeoSetLocalizationContract;
use Aerni\AdvancedSeo\Stache\Stores\SeoSetLocalizationsStore;
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

    $this->store = (new SeoSetLocalizationsStore($stache, $this->files))->directory(tempPath());
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

it('filters yaml files two levels deep', function (): void {
    $this->files->put(tempPath('root.yaml'), '');

    $this->files->ensureDirectoryExists(tempPath('collections/english'));
    $this->files->ensureDirectoryExists(tempPath('collections/french'));
    $this->files->put(tempPath('collections/english/articles.yaml'), '');
    $this->files->put(tempPath('collections/english/ignore.txt'), '');
    $this->files->put(tempPath('collections/french/articles.yaml'), '');

    $this->files->ensureDirectoryExists(tempPath('collections/english/nested'));
    $this->files->put(tempPath('collections/english/nested/ignore.yaml'), '');

    $files = Traverser::filter([$this->store, 'getItemFilter'])->traverse($this->store);

    expect($files->keys()->all())->toMatchArray([
        tempPath('collections/english/articles.yaml'),
        tempPath('collections/french/articles.yaml'),
    ]);

    /* Sanity check. Make sure the files are there but were not included. */
    expect(tempPath('root.yaml'))->toBeFile()
        ->and(tempPath('collections/english/ignore.txt'))->toBeFile()
        ->and(tempPath('collections/english/nested/ignore.yaml'))->toBeFile();
});

it('creates localization from file data', function (): void {
    $path = tempPath('collections/english/articles.yaml');

    $this->files->ensureDirectoryExists(tempPath('collections/english'));

    $this->files->put($path, <<<'YAML'
title: 'English Article'
YAML);

    $item = $this->store->makeItemFromFile($path, $this->files->get($path));

    expect($item)->toBeInstanceOf(SeoSetLocalizationContract::class)
        ->and($item->initialPath())->toBe($path)
        ->and($item->id())->toBe('collections::articles::english')
        ->and($item->locale())->toBe('english')
        ->and($item->data()->all())->toBe([
            'title' => 'English Article',
        ]);
});
