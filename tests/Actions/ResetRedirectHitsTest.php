<?php

use Aerni\AdvancedSeo\Actions\Statamic\ResetRedirectHits;
use Aerni\AdvancedSeo\Facades\Redirect;
use Statamic\Facades\Site;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

uses(PreventsSavingStacheItemsToDisk::class);

beforeEach(function () {
    Site::setSites(['default' => ['name' => 'Default', 'url' => '/', 'locale' => 'en']]);
    config(['advanced-seo.redirects.hits.enabled' => true]);
    $this->action = new ResetRedirectHits;
});

it('is visible for a redirect when hit logging is enabled', function () {
    $redirect = Redirect::make()->source('/a')->destination('/x')->site('default');

    expect($this->action->visibleTo($redirect))->toBeTrue();

    config(['advanced-seo.redirects.hits.enabled' => false]);

    expect($this->action->visibleTo($redirect))->toBeFalse();
});

it('deletes the hit records of the given redirects', function () {
    $redirect = Redirect::make()->id('r1')->source('/a')->destination('/x')->site('default')->save();
    Redirect::hits()->make()->redirect('r1')->count(9)->save();

    $this->action->run(collect([$redirect]), []);

    expect(Redirect::hits()->find('r1'))->toBeNull();
});

it('is a no-op for a redirect that has never been hit', function () {
    $redirect = Redirect::make()->id('r2')->source('/b')->destination('/y')->site('default')->save();

    $this->action->run(collect([$redirect]), []);

    expect(Redirect::hits()->find('r2'))->toBeNull();
});
