<?php

use Aerni\AdvancedSeo\Blueprints\ContentSeoSetConfigBlueprint;
use Aerni\AdvancedSeo\Context\Context;
use Aerni\AdvancedSeo\Enums\Scope;
use Aerni\AdvancedSeo\Facades\Seo;
use Aerni\AdvancedSeo\Tests\Concerns\EnablesRedirects;
use Statamic\Facades\Collection;
use Statamic\Facades\Site;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

uses(PreventsSavingStacheItemsToDisk::class, EnablesRedirects::class);

beforeEach(function () {
    Site::setSites([
        'english' => ['name' => 'English', 'url' => '/', 'locale' => 'en'],
    ]);

    Collection::make('pages')->sites(['english'])->saveQuietly();
});

function contentConfigBlueprintContext(): Context
{
    return new Context(
        parent: Collection::find('pages'),
        type: 'collections',
        handle: 'pages',
        scope: Scope::Config,
        site: 'english',
    );
}

it('includes the redirects toggle with an enabled default', function () {
    $fields = ContentSeoSetConfigBlueprint::resolve(contentConfigBlueprintContext())->fields()->all();

    expect($fields->has('redirects'))->toBeTrue()
        ->and($fields->get('redirects')->get('default'))->toBeTrue();
});

it('omits the redirects toggle on the free edition', function () {
    useFreeEdition();

    $fields = ContentSeoSetConfigBlueprint::resolve(contentConfigBlueprintContext())->fields()->all();

    expect($fields->has('redirects'))->toBeFalse();
});

it('omits the redirects toggle when the redirects feature is disabled', function () {
    config(['advanced-seo.redirects.enabled' => false]);

    $fields = ContentSeoSetConfigBlueprint::resolve(contentConfigBlueprintContext())->fields()->all();

    expect($fields->has('redirects'))->toBeFalse();
});

it('keeps the redirects toggle when seo is disabled for the collection', function () {
    Seo::find('collections::pages')->config()->enabled(false)->save();

    $fields = ContentSeoSetConfigBlueprint::resolve(contentConfigBlueprintContext())->fields()->all();

    expect($fields->has('redirects'))->toBeTrue();
});
