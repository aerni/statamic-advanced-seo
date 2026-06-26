<?php

use Aerni\AdvancedSeo\Enums\RedirectType;
use Aerni\AdvancedSeo\Facades\Redirects;
use Statamic\Facades\Site;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

uses(PreventsSavingStacheItemsToDisk::class);

beforeEach(function () {
    Site::setSites([
        'default' => ['name' => 'Default', 'url' => '/', 'locale' => 'en'],
    ]);
});

it('redirects a request that would 404 to the matched destination', function () {
    Redirects::make()->source('/old')->destination('/new')->type(RedirectType::Permanent)->site('default')->save();

    $this->get('/old')
        ->assertStatus(301)
        ->assertRedirect('/new');
});

it('forwards the query string when enabled', function () {
    config(['advanced-seo.redirects.forward_query_string' => true]);

    Redirects::make()->source('/old')->destination('/new')->site('default')->save();

    $this->get('/old?ref=abc')->assertRedirect('/new?ref=abc');
});

it('merges the query string into a destination that already has one', function () {
    config(['advanced-seo.redirects.forward_query_string' => true]);

    Redirects::make()->source('/old')->destination('/new?ref=internal')->site('default')->save();

    $this->get('/old?utm=x')->assertRedirect('/new?ref=internal&utm=x');
});

it('appends the query string before a destination fragment', function () {
    config(['advanced-seo.redirects.forward_query_string' => true]);

    Redirects::make()->source('/old')->destination('/new#section')->site('default')->save();

    $this->get('/old?ref=abc')->assertRedirect('/new?ref=abc#section');
});

it('returns 410 for a gone rule', function () {
    Redirects::make()->source('/gone')->type(RedirectType::Gone)->site('default')->save();

    $this->get('/gone')->assertStatus(410);
});

it('leaves an unmatched 404 alone', function () {
    $this->get('/genuinely-missing')->assertNotFound();
});

it('does not redirect a rule that points to its own path', function () {
    Redirects::make()->source('/old')->destination('/old')->site('default')->save();

    $this->get('/old')->assertNotFound();
});

it('still redirects to an external url with the same path', function () {
    Redirects::make()->source('/old')->destination('https://external.test/old')->site('default')->save();

    $this->get('/old')->assertRedirect('https://external.test/old');
});

it('does nothing when the feature is disabled', function () {
    config(['advanced-seo.redirects.enabled' => false]);

    Redirects::make()->source('/old')->destination('/new')->site('default')->save();

    $this->get('/old')->assertNotFound();
});

it('matches and redirects on a url-prefixed site', function () {
    Site::setSites([
        'default' => ['name' => 'Default', 'url' => '/', 'locale' => 'en'],
        'fr' => ['name' => 'French', 'url' => '/fr/', 'locale' => 'fr'],
    ]);

    Redirects::make()->source('/old')->destination('/new')->site('fr')->save();

    $this->get('/fr/old')->assertRedirect('/fr/new');
});

it('does not redirect non-GET requests', function () {
    Redirects::make()->source('/old')->destination('/new')->site('default')->save();

    $this->post('/old')->assertNotFound();
});
