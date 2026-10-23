<?php

use Aerni\AdvancedSeo\Facades\Seo;
use Aerni\AdvancedSeo\Fieldtypes\HreflangXDefaultFieldtype;
use Statamic\Facades\Role;
use Statamic\Facades\Site;
use Statamic\Facades\User;
use Statamic\Fields\Field;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

uses(PreventsSavingStacheItemsToDisk::class);

beforeEach(function () {
    Site::setSites([
        'dutch' => ['name' => 'Dutch', 'url' => 'https://example.com', 'locale' => 'nl'],
        'english' => ['name' => 'English', 'url' => 'https://example.com/en', 'locale' => 'en'],
        'german' => ['name' => 'German', 'url' => 'https://example.com/de', 'locale' => 'de'],
    ]);
});

function xDefaultFieldtype(): HreflangXDefaultFieldtype
{
    $field = new Field('hreflang_x_default', ['type' => 'hreflang_x_default']);
    $field->setParent(Seo::find('site::defaults'));

    return (new HreflangXDefaultFieldtype)->setField($field);
}

function actAsEnglishEditor(): void
{
    tap(Role::make('editor')->addPermission('access english site'))->save();
    test()->actingAs(tap(User::make()->assignRole('editor'))->save());
}

it('lists every site and locks the ones the user cannot access', function () {
    actAsEnglishEditor();

    expect(xDefaultFieldtype()->preload()['options'])->toBe([
        ['value' => 'dutch', 'label' => 'Dutch', 'disabled' => true],
        ['value' => 'english', 'label' => 'English', 'disabled' => false],
        ['value' => 'german', 'label' => 'German', 'disabled' => true],
    ]);
});
