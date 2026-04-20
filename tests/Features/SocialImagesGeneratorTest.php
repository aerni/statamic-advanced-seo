<?php

use Aerni\AdvancedSeo\Context\Context;
use Aerni\AdvancedSeo\Enums\Scope;
use Aerni\AdvancedSeo\Facades\Seo;
use Aerni\AdvancedSeo\Features\SocialImagesGenerator;
use Aerni\AdvancedSeo\Jobs\GenerateSocialImagesJob;
use Aerni\AdvancedSeo\Tests\Concerns\FakesComposerLock;
use Illuminate\Support\Facades\File;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Statamic\Facades\Site;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

uses(PreventsSavingStacheItemsToDisk::class, FakesComposerLock::class);

beforeEach(function () {
    Site::setSites([
        'english' => ['name' => 'English', 'url' => '/', 'locale' => 'en'],
    ]);

    Collection::make('pages')->sites(['english'])->saveQuietly();

    $this->installScreenshotPackage();

    config(['advanced-seo.social_images.generator.enabled' => true]);

    File::ensureDirectoryExists(resource_path('views/social_images/default'));
    File::put(resource_path('views/social_images/default/open_graph.antlers.html'), '');
});

afterEach(function () {
    File::deleteDirectory(resource_path('views/social_images'));
});

it('is disabled on the free edition', function () {
    useFreeEdition();

    expect(SocialImagesGenerator::enabled())->toBeFalse();
});

it('is enabled on the pro edition', function () {
    expect(SocialImagesGenerator::enabled())->toBeTrue();
});

it('is disabled when config is false', function () {
    config(['advanced-seo.social_images.generator.enabled' => false]);

    expect(SocialImagesGenerator::enabled())->toBeFalse();
});

it('is disabled when the screenshot package is not installed', function () {
    $this->uninstallPackages();

    expect(SocialImagesGenerator::enabled())->toBeFalse();
});

it('is disabled when no themes exist', function () {
    File::deleteDirectory(resource_path('views/social_images'));

    expect(SocialImagesGenerator::enabled())->toBeFalse();
});

it('is enabled when no context is provided', function () {
    expect(SocialImagesGenerator::enabled(null))->toBeTrue();
});

it('is enabled in config scope even when seoSet generator is disabled', function () {
    Seo::find('collections::pages')
        ->config()
        ->set('social_images_generator', false)
        ->save();

    $context = new Context(
        parent: Collection::find('pages'),
        type: 'collections',
        handle: 'pages',
        scope: Scope::Config,
        site: 'english',
    );

    expect(SocialImagesGenerator::enabled($context))->toBeTrue();
});

it('is disabled if the seoSet is disabled', function () {
    Seo::find('collections::pages')
        ->config()
        ->enabled(false)
        ->save();

    $context = new Context(
        parent: Collection::find('pages'),
        type: 'collections',
        handle: 'pages',
        scope: Scope::Localization,
        site: 'english',
    );

    expect(SocialImagesGenerator::enabled($context))->toBeFalse();
});

it('is disabled if the generator is disabled in the config', function () {
    Seo::find('collections::pages')
        ->config()
        ->set('social_images_generator', false)
        ->save();

    $context = new Context(
        parent: Collection::find('pages'),
        type: 'collections',
        handle: 'pages',
        scope: Scope::Content,
        site: 'english',
    );

    expect(SocialImagesGenerator::enabled($context))->toBeFalse();
});

it('prevents the make theme command on the free edition', function () {
    useFreeEdition();

    $this->artisan('seo:theme', ['name' => 'test'])
        ->assertSuccessful();

    expect(File::isDirectory(resource_path('views/social_images/test')))->toBeFalse();
});

it('prevents the generate job on the free edition', function () {
    useFreeEdition();

    $entry = Entry::make()
        ->collection('pages')
        ->locale('english')
        ->slug('test');

    $entry->saveQuietly();

    GenerateSocialImagesJob::dispatchSync($entry);

    expect($entry->get('seo_og_image'))->toBeNull();
});

it('shows in all contexts when enabled', function () {
    Seo::find('collections::pages')
        ->config()
        ->set('social_images_generator', true)
        ->save();

    foreach ([Scope::Config, Scope::Localization, Scope::Content] as $scope) {
        $context = new Context(
            parent: Collection::find('pages'),
            type: 'collections',
            handle: 'pages',
            scope: $scope,
            site: 'english',
        );

        expect(SocialImagesGenerator::enabled($context))->toBeTrue("Failed for scope: {$scope->value}");
    }
});
