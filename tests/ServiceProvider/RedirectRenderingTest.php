<?php

use Aerni\AdvancedSeo\ServiceProvider;
use Illuminate\Http\Request;
use Statamic\Exceptions\NotFoundHttpException;

afterEach(function () {
    NotFoundHttpException::renderUsing(fn () => null);
});

it('does not replace the existing not found renderer when redirects are disabled', function () {
    NotFoundHttpException::renderUsing(fn () => response('existing renderer', 418));

    $provider = app()->make(ServiceProvider::class, ['app' => app()]);

    (fn () => $this->bootRedirects())->call($provider);

    $response = (new NotFoundHttpException)->render(Request::create('/missing'));

    expect($response->getStatusCode())->toBe(418)
        ->and($response->getContent())->toBe('existing renderer');
});
