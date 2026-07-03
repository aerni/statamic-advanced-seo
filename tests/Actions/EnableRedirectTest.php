<?php

use Aerni\AdvancedSeo\Actions\Statamic\EnableRedirect;
use Aerni\AdvancedSeo\Facades\Redirect;
use Statamic\Facades\Site;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

uses(PreventsSavingStacheItemsToDisk::class);

beforeEach(function () {
    Site::setSites(['default' => ['name' => 'Default', 'url' => '/', 'locale' => 'en']]);
    $this->action = new EnableRedirect;
});

it('is visible only for a disabled redirect', function () {
    $enabled = Redirect::make()->source('/a')->destination('/x')->site('default')->enabled(true);
    $disabled = Redirect::make()->source('/b')->destination('/y')->site('default')->enabled(false);

    expect($this->action->visibleTo($disabled))->toBeTrue()
        ->and($this->action->visibleTo($enabled))->toBeFalse();
});

it('shows in bulk when at least one selected redirect is disabled', function () {
    $enabled = Redirect::make()->source('/a')->destination('/x')->site('default')->enabled(true);
    $disabled = Redirect::make()->source('/b')->destination('/y')->site('default')->enabled(false);

    expect($this->action->visibleToBulk(collect([$enabled, $disabled])))->toBeTrue()
        ->and($this->action->visibleToBulk(collect([$enabled])))->toBeFalse();
});

it('enables the redirects', function () {
    $redirect = Redirect::make()->source('/a')->destination('/x')->site('default')->enabled(false)->save();

    $this->action->run(collect([$redirect]), []);

    expect(Redirect::find($redirect->id())->enabled())->toBeTrue();
});
