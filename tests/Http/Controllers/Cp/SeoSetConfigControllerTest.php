<?php

use Aerni\AdvancedSeo\Facades\Seo;
use Statamic\Facades\Role;
use Statamic\Facades\Site;
use Statamic\Facades\User;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

uses(PreventsSavingStacheItemsToDisk::class);

beforeEach(function () {
    Site::setSites([
        'dutch' => ['name' => 'Dutch', 'url' => 'https://example.com', 'locale' => 'nl'],
        'english' => ['name' => 'English', 'url' => 'https://example.com/en', 'locale' => 'en'],
        'german' => ['name' => 'German', 'url' => 'https://example.com/de', 'locale' => 'de'],
    ]);

    Seo::find('site::defaults')->config()->set('hreflang_x_default', 'dutch')->save();

    tap(Role::make('editor')->addPermission([
        'access cp',
        'configure seo',
        'access english site',
    ]))->save();

    $this->editor = tap(User::make()->assignRole('editor'))->save();
});

function siteConfigPayload(string $site): array
{
    return [
        'hreflang_x_default' => $site,
        'origins' => [
            ['handle' => 'dutch', 'origin' => null],
            ['handle' => 'english', 'origin' => null],
            ['handle' => 'german', 'origin' => null],
        ],
    ];
}

it('shows an inaccessible x-default site as a locked option', function (string $edition) {
    config(['statamic.editions.addons.aerni/advanced-seo' => $edition]);
    flushBlink();

    $this->actingAs($this->editor)
        ->getJson(cp_route('advanced-seo.sets.config.edit', [
            'seoSetGroup' => 'site',
            'seoSet' => 'defaults',
        ]))
        ->assertOk()
        ->assertJsonPath('values.hreflang_x_default', 'dutch')
        ->assertJsonPath('meta.hreflang_x_default.options', [
            ['value' => 'dutch', 'label' => 'Dutch', 'disabled' => true],
            ['value' => 'english', 'label' => 'English', 'disabled' => false],
            ['value' => 'german', 'label' => 'German', 'disabled' => true],
        ]);
})->with(['free', 'pro']);

it('stores the submitted x-default site', function () {
    $this->actingAs($this->editor)
        ->patch(cp_route('advanced-seo.sets.config.update', [
            'seoSetGroup' => 'site',
            'seoSet' => 'defaults',
        ]), siteConfigPayload('english'))
        ->assertSuccessful();

    expect(Seo::find('site::defaults')->config()->get('hreflang_x_default'))->toBe('english');
});

it('preserves a saved x-default site the editor cannot access', function (string $edition) {
    config(['statamic.editions.addons.aerni/advanced-seo' => $edition]);
    flushBlink();

    $this->actingAs($this->editor)
        ->patch(cp_route('advanced-seo.sets.config.update', [
            'seoSetGroup' => 'site',
            'seoSet' => 'defaults',
        ]), siteConfigPayload('dutch'))
        ->assertSuccessful();

    expect(Seo::find('site::defaults')->config()->get('hreflang_x_default'))->toBe('dutch');
})->with(['free', 'pro']);
