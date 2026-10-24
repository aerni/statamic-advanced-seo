<?php

use Aerni\AdvancedSeo\Actions\ViewExistsForSite;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Statamic\Facades\Site;
use Statamic\Http\Middleware\AddViewPaths;

beforeEach(function () {
    $this->originalViewFinder = view()->getFinder();
    $this->viewDirectory = tempPath('view-exists-for-site-'.Str::uuid());
    File::ensureDirectoryExists($this->viewDirectory.'/english');
    File::put($this->viewDirectory.'/english/page.antlers.html', 'English page');
    $finder = clone $this->originalViewFinder;
    $finder->setPaths([$this->viewDirectory]);
    $finder->replaceNamespace('theme', [$this->viewDirectory]);
    $finder->flush();
    view()->setFinder($finder);

    Site::setSites([
        'english' => ['url' => 'https://example.com', 'locale' => 'en'],
        'german' => ['url' => 'https://example.com/de', 'locale' => 'de'],
    ]);
    Site::setCurrent('english');
});

afterEach(function () {
    view()->setFinder($this->originalViewFinder);
    File::deleteDirectory($this->viewDirectory);
});

it('finds site-specific templates without HTTP middleware', function (string $view) {
    expect(ViewExistsForSite::run($view, 'english'))->toBeTrue()
        ->and(ViewExistsForSite::run($view, 'german'))->toBeFalse();
})->with(['page', 'theme::page']);

it('uses shared templates when the site has no override', function (string $view) {
    File::put($this->viewDirectory.'/page.blade.php', 'Shared page');

    expect(ViewExistsForSite::run($view, 'german'))->toBeTrue();
})->with(['page', 'theme::page']);

it('does not borrow the request site template or its cached resolution', function (string $view) {
    app(AddViewPaths::class)->handle(request(), function () use ($view) {
        view()->getFinder()->find($view);
        $finder = view()->getFinder();
        $paths = $finder->getPaths();
        $hints = $finder->getHints();
        $views = $finder->getViews();
        Site::setCurrent('german');

        expect(ViewExistsForSite::run($view, 'german'))->toBeFalse()
            ->and(ViewExistsForSite::run($view, 'english'))->toBeTrue()
            ->and(view()->getFinder())->toBe($finder)
            ->and($finder->getPaths())->toBe($paths)
            ->and($finder->getHints())->toBe($hints)
            ->and($finder->getViews())->toBe($views)
            ->and(Site::current()->handle())->toBe('german');

        return response('ok');
    });
})->with(['page', 'theme::page']);

it('removes request site paths when registered roots have trailing slashes', function (string $view) {
    view()->getFinder()->setPaths([$this->viewDirectory.'/']);
    view()->getFinder()->replaceNamespace('theme', [$this->viewDirectory.'/']);

    app(AddViewPaths::class)->handle(request(), function () use ($view) {
        expect(ViewExistsForSite::run($view, 'english'))->toBeTrue()
            ->and(ViewExistsForSite::run($view, 'german'))->toBeFalse();

        return response('ok');
    });
})->with(['page', 'theme::page']);

it('uses updated view roots after an earlier lookup', function (string $view) {
    expect(ViewExistsForSite::run($view, 'german'))->toBeFalse();

    view()->getFinder()->setPaths([$this->viewDirectory.'/english']);
    view()->getFinder()->replaceNamespace('theme', [$this->viewDirectory.'/english']);

    expect(ViewExistsForSite::run($view, 'german'))->toBeTrue();
})->with(['page', 'theme::page']);

it('finds a template created after an earlier missing lookup', function (string $view) {
    expect(ViewExistsForSite::run($view, 'german'))->toBeFalse();

    File::ensureDirectoryExists($this->viewDirectory.'/german');
    File::put($this->viewDirectory.'/german/page.antlers.html', 'German page');

    expect(ViewExistsForSite::run($view, 'german'))->toBeTrue();
})->with(['page', 'theme::page']);
