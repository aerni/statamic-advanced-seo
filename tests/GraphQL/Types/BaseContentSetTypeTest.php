<?php

use Aerni\AdvancedSeo\Facades\Seo;
use Aerni\AdvancedSeo\GraphQL\Types\BaseContentSetType;
use GraphQL\Type\Definition\ResolveInfo;
use Statamic\Facades\Collection;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

uses(PreventsSavingStacheItemsToDisk::class);

beforeEach(function () {
    Collection::make('pages')->saveQuietly();
});

it('resolves data from SeoSetLocalization using SeoSetLocalizationResolver', function () {
    Seo::find('collections::pages')
        ->inDefaultSite()
        ->set('seo_title', 'Test Title')
        ->save();

    clearStache();

    $localization = Seo::find('collections::pages')->inDefaultSite();

    $info = Mockery::mock(ResolveInfo::class);
    $info->fieldName = 'title';

    $result = (new TestContentSet)->fields()['title']['resolve']($localization, [], null, $info);

    expect($result)->toBe('Test Title');
});

class TestContentSet extends BaseContentSetType
{
    const NAME = 'testContentSet';

    protected $attributes = [
        'name' => self::NAME,
    ];
}
