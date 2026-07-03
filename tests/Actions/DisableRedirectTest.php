<?php

use Aerni\AdvancedSeo\Actions\Statamic\DisableRedirect;
use Aerni\AdvancedSeo\Facades\Redirect;
use Statamic\Facades\Site;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

uses(PreventsSavingStacheItemsToDisk::class);

beforeEach(function () {
    Site::setSites(['default' => ['name' => 'Default', 'url' => '/', 'locale' => 'en']]);
    $this->action = new DisableRedirect;
});

it('is visible only for an enabled redirect', function () {
    $enabled = Redirect::make()->source('/a')->destination('/x')->site('default')->enabled(true);
    $disabled = Redirect::make()->source('/b')->destination('/y')->site('default')->enabled(false);

    expect($this->action->visibleTo($enabled))->toBeTrue()
        ->and($this->action->visibleTo($disabled))->toBeFalse();
});

it('shows in bulk when at least one selected redirect is enabled', function () {
    $enabled = Redirect::make()->source('/a')->destination('/x')->site('default')->enabled(true);
    $disabled = Redirect::make()->source('/b')->destination('/y')->site('default')->enabled(false);

    expect($this->action->visibleToBulk(collect([$enabled, $disabled])))->toBeTrue()
        ->and($this->action->visibleToBulk(collect([$disabled])))->toBeFalse();
});

it('disables the redirects', function () {
    $redirect = Redirect::make()->source('/a')->destination('/x')->site('default')->enabled(true)->save();

    $this->action->run(collect([$redirect]), []);

    expect(Redirect::find($redirect->id())->enabled())->toBeFalse();
});
