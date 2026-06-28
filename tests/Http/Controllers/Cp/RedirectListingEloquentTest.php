<?php

use Aerni\AdvancedSeo\Enums\RedirectType;
use Aerni\AdvancedSeo\Facades\Redirects;
use Aerni\AdvancedSeo\Tests\Concerns\UseEloquentDriver;
use Statamic\Facades\Role;
use Statamic\Facades\Site;
use Statamic\Facades\User;

uses(UseEloquentDriver::class);

beforeEach(function () {
    Site::setSites([
        'default' => ['name' => 'Default', 'url' => '/', 'locale' => 'en'],
        'french' => ['name' => 'French', 'url' => '/fr/', 'locale' => 'fr'],
    ]);
    $this->super = tap(User::make()->makeSuper())->save();
});

it('returns all authorized sites redirects as json for a super user', function () {
    Redirects::make()->source('/old-en')->destination('/new')->site('default')->save();
    Redirects::make()->source('/old-fr')->destination('/nouveau')->site('french')->save();

    $response = $this->actingAs($this->super)
        ->getJson(cp_route('advanced-seo.redirects.index'))
        ->assertOk()
        ->assertJsonStructure(['data', 'meta' => ['columns'], 'links']);

    expect($response->json('meta.total'))->toBe(2);
});

it('searches by source and destination', function () {
    Redirects::make()->source('/alpha')->destination('/x')->site('default')->save();
    Redirects::make()->source('/beta')->destination('/y')->site('default')->save();

    $data = $this->actingAs($this->super)
        ->getJson(cp_route('advanced-seo.redirects.index', ['search' => 'alpha']))
        ->json('data');

    expect($data)->toHaveCount(1)->and($data[0]['source'])->toBe('/alpha');
});

it('paginates', function () {
    foreach (range(1, 30) as $i) {
        Redirects::make()->source("/p{$i}")->destination('/x')->site('default')->save();
    }

    $response = $this->actingAs($this->super)
        ->getJson(cp_route('advanced-seo.redirects.index', ['perPage' => 10]))
        ->assertOk();

    expect($response->json('data'))->toHaveCount(10)
        ->and($response->json('meta.total'))->toBe(30);
});

it('filters by type', function () {
    Redirects::make()->source('/perm')->destination('/x')->site('default')->type(RedirectType::Permanent)->save();
    Redirects::make()->source('/temp')->destination('/y')->site('default')->type(RedirectType::Temporary)->save();

    $filters = base64_encode(json_encode(['redirect_type' => ['type' => '302']]));

    $data = $this->actingAs($this->super)
        ->getJson(cp_route('advanced-seo.redirects.index', ['filters' => $filters]))
        ->json('data');

    expect($data)->toHaveCount(1)->and($data[0]['source'])->toBe('/temp');
});

it('filters by status', function () {
    Redirects::make()->source('/enabled')->destination('/x')->site('default')->enabled(true)->save();
    Redirects::make()->source('/disabled')->destination('/y')->site('default')->enabled(false)->save();

    $filters = base64_encode(json_encode(['redirect_status' => ['enabled' => 'false']]));

    $data = $this->actingAs($this->super)
        ->getJson(cp_route('advanced-seo.redirects.index', ['filters' => $filters]))
        ->json('data');

    expect($data)->toHaveCount(1)->and($data[0]['source'])->toBe('/disabled');
});

it('filters by site', function () {
    Redirects::make()->source('/en-page')->destination('/x')->site('default')->save();
    Redirects::make()->source('/fr-page')->destination('/y')->site('french')->save();

    $filters = base64_encode(json_encode(['redirect_site' => ['site' => 'french']]));

    $data = $this->actingAs($this->super)
        ->getJson(cp_route('advanced-seo.redirects.index', ['filters' => $filters]))
        ->json('data');

    expect($data)->toHaveCount(1)->and($data[0]['source'])->toBe('/fr-page');
});

it('only returns redirects for sites a non-super user is authorized to view', function () {
    Redirects::make()->source('/en-page')->destination('/x')->site('default')->save();
    Redirects::make()->source('/fr-page')->destination('/y')->site('french')->save();

    $role = tap(Role::make('default_viewer')->addPermission(['access cp', 'view redirects', 'access default site']))->save();
    $user = tap(User::make()->assignRole('default_viewer'))->save();

    $data = $this->actingAs($user)
        ->getJson(cp_route('advanced-seo.redirects.index'))
        ->assertOk()
        ->json('data');

    expect($data)->toHaveCount(1)->and($data[0]['source'])->toBe('/en-page');
});
