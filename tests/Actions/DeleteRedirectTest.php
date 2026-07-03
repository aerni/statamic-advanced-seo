<?php

use Aerni\AdvancedSeo\Actions\Statamic\DeleteRedirect;
use Aerni\AdvancedSeo\Facades\Redirect;
use Statamic\Facades\Site;
use Statamic\Facades\User;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

uses(PreventsSavingStacheItemsToDisk::class);

beforeEach(function () {
    Site::setSites(['default' => ['name' => 'Default', 'url' => '/', 'locale' => 'en']]);
    $this->action = new DeleteRedirect;
});

it('is visible only for redirects', function () {
    $redirect = Redirect::make()->source('/a')->destination('/x')->site('default');

    expect($this->action->visibleTo($redirect))->toBeTrue()
        ->and($this->action->visibleTo(new stdClass))->toBeFalse();
});

it('authorizes a user who can delete', function () {
    $super = tap(User::make()->makeSuper())->save();
    $redirect = Redirect::make()->source('/a')->destination('/x')->site('default');

    expect($this->action->authorize($super, $redirect))->toBeTrue();
});

it('deletes the redirects', function () {
    $redirect = Redirect::make()->source('/a')->destination('/x')->site('default')->save();

    $this->action->run(collect([$redirect]), []);

    expect(Redirect::query()->get())->toHaveCount(0);
});
