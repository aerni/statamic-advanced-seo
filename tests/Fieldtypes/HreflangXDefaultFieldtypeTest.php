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
        ['value' => 'dutch', 'label' => 'Dutch', 'disabled' => true, 'icon' => 'padlock-locked'],
        ['value' => 'english', 'label' => 'English', 'disabled' => false],
        ['value' => 'german', 'label' => 'German', 'disabled' => true, 'icon' => 'padlock-locked'],
    ]);
});

it('leaves every site selectable when nobody is signed in', function () {
    expect(collect(xDefaultFieldtype()->preload()['options'])->pluck('disabled')->unique()->all())->toBe([false]);
});

it('keeps a stored site the user cannot access when that value is submitted again', function () {
    Seo::find('site::defaults')->config()->set('hreflang_x_default', 'dutch')->save();
    actAsEnglishEditor();

    expect(xDefaultFieldtype()->process('dutch'))->toBe('dutch');
});

it('keeps a stored site the user cannot access when the submitted value is empty', function () {
    Seo::find('site::defaults')->config()->set('hreflang_x_default', 'dutch')->save();
    actAsEnglishEditor();

    expect(xDefaultFieldtype()->process(null))->toBe('dutch');
});

it('keeps a stored site the user cannot access when another inaccessible site is submitted', function () {
    Seo::find('site::defaults')->config()->set('hreflang_x_default', 'dutch')->save();
    actAsEnglishEditor();

    expect(xDefaultFieldtype()->process('german'))->toBe('dutch');
});

it('stores a site when the user picks one they can access', function () {
    Seo::find('site::defaults')->config()->set('hreflang_x_default', 'dutch')->save();
    actAsEnglishEditor();

    expect(xDefaultFieldtype()->process('english'))->toBe('english');
});
