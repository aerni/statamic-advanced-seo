<?php

use Aerni\AdvancedSeo\Cascades\ContentCascade;
use Aerni\AdvancedSeo\Facades\Seo;
use Aerni\AdvancedSeo\Facades\SocialImage;
use Aerni\AdvancedSeo\Tests\Concerns\FakesComposerLock;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use League\Flysystem\UnableToRetrieveMetadata;
use Spatie\LaravelScreenshot\Facades\Screenshot;
use Spatie\LaravelScreenshot\ScreenshotBuilder;
use Statamic\Contracts\Assets\Asset;
use Statamic\Contracts\Entries\Entry;
use Statamic\Facades\AssetContainer;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry as EntryFacade;
use Statamic\Facades\Site;
use Statamic\Statamic;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

uses(PreventsSavingStacheItemsToDisk::class, FakesComposerLock::class);

beforeEach(function () {
    Site::setSites([
        'english' => ['name' => 'English', 'url' => 'https://example.com', 'locale' => 'en'],
    ]);

    Collection::make('pages')->routes('/{slug}')->sites(['english'])->saveQuietly();
    AssetContainer::make('assets')->disk('local')->saveQuietly();

    $this->installScreenshotPackage();

    config(['advanced-seo.social_images.generator.enabled' => true]);

    File::ensureDirectoryExists(resource_path('views/social_images/default'));
    File::put(resource_path('views/social_images/default/open_graph.antlers.html'), 'social image');

    Seo::find('collections::pages')->config()->set('social_images_generator', true)->save();

    Http::fake(['*' => Http::response('<html>social image</html>')]);

    Carbon::setTestNow('2026-11-06 22:00:00');
});

afterEach(function () {
    Carbon::setTestNow();

    $container = AssetContainer::find('assets');

    if ($container) {
        File::deleteDirectory($container->disk()->path('social_images'));
    }

    File::deleteDirectory(resource_path('views/social_images'));
});

it('renders the seo head when the generated social image file is missing', function () {
    $entry = socialImageEntry();
    $asset = generateSocialImage($entry);

    removeSocialImageFile($asset);

    $html = view('advanced-seo::head', [
        'seo' => ContentCascade::from($entry),
    ])->render();

    expect($html)->toBeString();

    if (str_contains($html, $asset->basename())) {
        expect($asset->container()->disk()->exists($asset->path()))->toBeTrue();
    }
});

it('omits a missing social image when it cannot be regenerated', function () {
    $entry = socialImageEntry();
    $asset = generateSocialImage($entry);

    removeSocialImageFile($asset);
    socialImageScreenshotFails();

    $html = view('advanced-seo::head', [
        'seo' => ContentCascade::from($entry),
    ])->render();

    expect($html)->not->toContain($asset->basename());
});

it('uses the default open graph image when the generated file cannot be regenerated', function () {
    $container = AssetContainer::find('assets');
    $defaultPath = 'social_images/default-og.png';

    File::ensureDirectoryExists(dirname($container->disk()->path($defaultPath)));
    File::put($container->disk()->path($defaultPath), socialImagePng());
    $container->makeAsset($defaultPath)->save();

    Seo::find('site::defaults')->in('english')->set('og_image', $defaultPath)->save();

    $entry = socialImageEntry();
    $asset = generateSocialImage($entry);

    removeSocialImageFile($asset);
    socialImageScreenshotFails();

    $html = view('advanced-seo::head', [
        'seo' => ContentCascade::from($entry),
    ])->render();

    expect($html)
        ->toContain('default-og.png')
        ->not->toContain($asset->basename());
});

it('renders the seo head when a selected social image file is missing', function () {
    $entry = socialImageEntry();
    $asset = generateSocialImage($entry);

    $entry->set('seo_generate_social_images', false);
    $entry->set('seo_og_image', $asset->path());
    $entry->save();

    removeSocialImageFile($asset);

    $html = view('advanced-seo::head', [
        'seo' => ContentCascade::from($entry),
    ])->render();

    expect($html)->not->toContain($asset->basename());
});

it('regenerates a social image when the previous file is missing from disk', function () {
    $entry = socialImageEntry();
    $stale = generateSocialImage($entry);

    removeSocialImageFile($stale);

    Carbon::setTestNow('2026-11-09 11:00:00');

    $asset = SocialImage::openGraph()->for($entry)->generate();

    expect($asset->container()->disk()->exists($asset->path()))->toBeTrue()
        ->and($asset->path())->not->toBe($stale->path())
        ->and($stale->container()->disk()->exists($stale->path()))->toBeFalse();
});

it('treats a generated social image as dirty when its file is missing', function () {
    $entry = socialImageEntry();
    $asset = generateSocialImage($entry);
    $generator = SocialImage::openGraph()->for($entry);

    expect($generator->isDirty())->toBeFalse();

    removeSocialImageFile($asset);

    expect($generator->isDirty())->toBeTrue()
        ->and($generator->asset())->toBeNull();
});

it('does not read metadata while removing a social image that is already gone', function () {
    $entry = socialImageEntry();
    $asset = generateSocialImage($entry);

    removeSocialImageFile($asset);
    request()->server->set('argv', ['artisan', 'queue:work']);
    Carbon::setTestNow('2026-11-09 11:00:00');

    expect(Statamic::isWorker())->toBeTrue();

    expect(fn () => SocialImage::openGraph()->for($entry)->generate())
        ->not->toThrow(UnableToRetrieveMetadata::class);

    expect(AssetContainer::find('assets')->asset($asset->path()))->toBeNull();
});

function socialImageEntry(): Entry
{
    return tap(EntryFacade::make()
        ->collection('pages')
        ->locale('english')
        ->slug('launch')
        ->data([
            'title' => 'Launch',
            'seo_generate_social_images' => true,
            'seo_social_images_theme' => 'default',
        ]))->save();
}

function generateSocialImage(Entry $entry): Asset
{
    fakeSocialImageScreenshot();

    return SocialImage::openGraph()->for($entry)->generate();
}

function removeSocialImageFile(Asset $asset): void
{
    $disk = $asset->container()->disk();

    File::delete($disk->path($asset->path()));
    File::delete($disk->path($asset->metaPath()));
    Cache::forget($asset->metaCacheKey());
}

function socialImagePng(): string
{
    return base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');
}

function socialImageScreenshotFails(): void
{
    $screenshot = Mockery::mock(ScreenshotBuilder::class);
    $screenshot->shouldReceive('url')->andThrow(new RuntimeException('browsershot failed'));

    Screenshot::swap($screenshot);
}

function fakeSocialImageScreenshot(): void
{
    $png = socialImagePng();

    Screenshot::shouldReceive('url')->andReturnUsing(function () use ($png) {
        $builder = Mockery::mock(ScreenshotBuilder::class);
        $builder->shouldReceive('width', 'height', 'deviceScaleFactor', 'withBrowsershot')->andReturnSelf();
        $builder->shouldReceive('save')->andReturnUsing(function (string $path) use ($png, $builder) {
            File::ensureDirectoryExists(dirname($path));
            File::put($path, $png);

            return $builder;
        });

        return $builder;
    });
}
