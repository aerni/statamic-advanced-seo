<?php

use Aerni\AdvancedSeo\Facades\Redirect;
use Aerni\AdvancedSeo\Tests\Concerns\EnablesRedirects;
use Aerni\AdvancedSeo\Tests\Concerns\FakesComposerLock;
use Statamic\Facades\Collection;
use Statamic\Facades\Role;
use Statamic\Facades\Site;
use Statamic\Facades\User;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

uses(PreventsSavingStacheItemsToDisk::class, FakesComposerLock::class, EnablesRedirects::class);

beforeEach(function () {
    Site::setSites(['default' => ['name' => 'Default', 'url' => '/', 'locale' => 'en']]);
    Collection::make('pages')->routes('/{slug}')->sites(['default'])->saveQuietly();
    $this->installSimpleExcelPackage();
    $this->super = tap(User::make()->makeSuper())->save();
});

it('downloads a csv export', function () {
    $this->freezeTime();

    Redirect::make()->source('/old')->destination('/new')->site('default')->save();

    $response = $this->actingAs($this->super)
        ->get(cp_route('advanced-seo.redirects.export', ['format' => 'csv']))
        ->assertOk()
        ->assertDownload('redirects-'.now()->format('Y-m-d-His').'.csv')
        ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

    expect($response->streamedContent())->toContain('/old');
});

it('downloads a json export', function () {
    $this->freezeTime();

    Redirect::make()->source('/old')->destination('/new')->site('default')->save();

    $this->actingAs($this->super)
        ->get(cp_route('advanced-seo.redirects.export', ['format' => 'json']))
        ->assertOk()
        ->assertDownload('redirects-'.now()->format('Y-m-d-His').'.json')
        ->assertHeader('Content-Type', 'application/json');
});

it('exports only redirects matching the current filters', function () {
    Site::setSites([
        'default' => ['name' => 'English', 'url' => '/', 'locale' => 'en'],
        'german' => ['name' => 'German', 'url' => '/de/', 'locale' => 'de'],
    ]);

    Redirect::make()->source('/english')->destination('/new')->site('default')->save();
    Redirect::make()->source('/german')->destination('/neu')->site('german')->save();

    $filters = base64_encode(json_encode(['redirect_site' => ['site' => 'default']]));

    $response = $this->actingAs($this->super)
        ->get(cp_route('advanced-seo.redirects.export', ['format' => 'csv', 'filters' => $filters]))
        ->assertOk();

    expect($response->streamedContent())
        ->toContain('/english')
        ->not->toContain('/german');
});

it('exports only redirects matching the current search', function () {
    Redirect::make()->source('/matching')->destination('/new')->site('default')->save();
    Redirect::make()->source('/another')->destination('/target')->site('default')->save();

    $response = $this->actingAs($this->super)
        ->get(cp_route('advanced-seo.redirects.export', ['format' => 'csv', 'search' => 'matching']))
        ->assertOk();

    expect($response->streamedContent())
        ->toContain('/matching')
        ->not->toContain('/another');
});

it('exports all redirects using the current sort order', function (string $order, array $expectedSources) {
    Redirect::make()->source('/beta')->destination('/new')->site('default')->save();
    Redirect::make()->source('/alpha')->destination('/target')->site('default')->save();

    $response = $this->actingAs($this->super)
        ->get(cp_route('advanced-seo.redirects.export', [
            'format' => 'json',
            'sort' => 'source',
            'order' => $order,
        ]))
        ->assertOk();

    expect(collect(json_decode($response->streamedContent(), true))->pluck('source')->all())
        ->toBe($expectedSources);
})->with([
    'ascending' => ['asc', ['/alpha', '/beta']],
    'descending' => ['desc', ['/beta', '/alpha']],
]);

it('intersects current filters with the sites the user is authorized to access', function () {
    Site::setSites([
        'default' => ['name' => 'English', 'url' => '/', 'locale' => 'en'],
        'german' => ['name' => 'German', 'url' => '/de/', 'locale' => 'de'],
    ]);

    Redirect::make()->source('/english')->destination('/new')->site('default')->save();
    Redirect::make()->source('/german')->destination('/neu')->site('german')->save();

    tap(Role::make('english_editor')->addPermission(['access cp', 'manage redirects', 'access default site']))->save();
    $editor = tap(User::make()->assignRole('english_editor'))->save();
    $filters = base64_encode(json_encode(['redirect_site' => ['site' => 'german']]));

    $response = $this->actingAs($editor)
        ->get(cp_route('advanced-seo.redirects.export', ['format' => 'csv', 'filters' => $filters]))
        ->assertOk();

    expect($response->streamedContent())
        ->not->toContain('/english')
        ->not->toContain('/german');
});

it('404s on an unknown format', function () {
    $this->actingAs($this->super)->get('/cp/advanced-seo/redirects/export/xml')->assertNotFound();
});

it('returns 404 when simple-excel is not installed', function () {
    $this->uninstallPackages();

    $this->actingAs($this->super)->get(cp_route('advanced-seo.redirects.export', ['format' => 'csv']))->assertNotFound();
});

it('forbids a user without the manage permission', function () {
    tap(Role::make('viewer')->addPermission(['access cp', 'access default site']))->save();
    $viewer = tap(User::make()->assignRole('viewer'))->save();

    $this->actingAs($viewer)->getJson(cp_route('advanced-seo.redirects.export', ['format' => 'csv']))->assertForbidden();
});
