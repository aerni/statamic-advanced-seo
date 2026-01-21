<?php

use Aerni\AdvancedSeo\Facades\Seo;
use Aerni\AdvancedSeo\GraphQL\Resolvers\SeoSetLocalizationResolver;
use GraphQL\Type\Definition\ResolveInfo;
use Statamic\Facades\Collection;
use Statamic\Facades\Site;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

uses(PreventsSavingStacheItemsToDisk::class);

beforeEach(function () {
    Site::setSites([
        'english' => ['name' => 'English', 'url' => '/', 'locale' => 'en'],
    ]);

    Collection::make('pages')->saveQuietly();
});

it('resolves field value when no custom resolver exists', function () {
    Seo::find('collections::pages')
        ->inDefaultSite()
        ->set('seo_title', 'Test Title')
        ->save();

    clearStache();

    $resolver = SeoSetLocalizationResolver::resolve(
        field: ['resolve' => null],
        handle: 'seo_title'
    );

    $info = Mockery::mock(ResolveInfo::class);
    $info->fieldName = 'title'; // GraphQL field name without prefix

    $result = $resolver(
        localization: Seo::find('collections::pages')->inDefaultSite(),
        args: [],
        context: null,
        info: $info
    );

    expect($result)->toBe('Test Title');
});

it('calls custom resolver with corrected field name', function () {
    $customResolverCalled = false;
    $receivedFieldName = null;

    $customResolver = function ($loc, $args, $context, $info) use (&$customResolverCalled, &$receivedFieldName) {
        $customResolverCalled = true;
        $receivedFieldName = $info->fieldName;

        return 'custom value';
    };

    $resolver = SeoSetLocalizationResolver::resolve(
        field: ['resolve' => $customResolver],
        handle: 'seo_description'
    );

    /* Use makePartial to allow property assignment on the mock */
    $info = Mockery::mock(ResolveInfo::class)->makePartial();
    $info->fieldName = 'description'; // GraphQL field name without prefix

    $result = $resolver(
        localization: Seo::find('collections::pages')->inDefaultSite(),
        args: [],
        context: null,
        info: $info
    );

    expect($customResolverCalled)->toBeTrue();
    expect($receivedFieldName)->toBe('seo_description'); // Should be the full blueprint handle
    expect($result)->toBe('custom value');
});
