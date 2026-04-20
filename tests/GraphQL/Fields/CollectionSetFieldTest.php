<?php

use Aerni\AdvancedSeo\Contracts\SeoSetLocalization;
use Aerni\AdvancedSeo\GraphQL\Fields\CollectionSetField;
use Aerni\AdvancedSeo\GraphQL\Types\CollectionSetType;
use GraphQL\Type\Definition\ResolveInfo;
use Statamic\Facades\Collection;
use Statamic\Facades\Site;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

uses(PreventsSavingStacheItemsToDisk::class);

it('returns the CollectionSetType', function () {
    expect((new CollectionSetField)->type()->name)->toBe(CollectionSetType::NAME);
});

it('has handle and site arguments', function () {
    $args = (new CollectionSetField)->args();

    expect($args)->toHaveKeys(['handle', 'site']);
    expect($args['handle']['type']->getWrappedType()->name)->toBe('String');
});

it('resolves via SeoSetResolver', function () {
    Site::setSites([
        'english' => ['name' => 'English', 'url' => '/', 'locale' => 'en'],
    ]);

    Collection::make('pages')->sites(['english'])->saveQuietly();

    $info = Mockery::mock(ResolveInfo::class);
    $info->fieldName = 'collection';

    $result = invade(new CollectionSetField)->resolve(null, ['handle' => 'pages'], null, $info);

    expect($result)->toBeInstanceOf(SeoSetLocalization::class);
    expect($result->handle())->toBe('pages');
});
