<?php

use Aerni\AdvancedSeo\Enums\ResponseCode;
use Aerni\AdvancedSeo\Facades\Redirect;
use Aerni\AdvancedSeo\Jobs\RecordRedirectErrorJob;
use Aerni\AdvancedSeo\Jobs\RecordRedirectHitJob;
use Illuminate\Support\Facades\Queue;
use Statamic\Facades\Site;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

uses(PreventsSavingStacheItemsToDisk::class);

beforeEach(function () {
    Site::setSites([
        'default' => ['name' => 'Default', 'url' => '/', 'locale' => 'en'],
    ]);
});

it('redirects a request that would 404 to the matched destination', function () {
    Redirect::make()->source('/old')->destination('/new')->responseCode(ResponseCode::Permanent)->site('default')->save();

    $this->get('/old')
        ->assertStatus(301)
        ->assertRedirect('/new');
});

it('forwards the query string by default', function () {
    Redirect::make()->source('/old')->destination('/new')->site('default')->save();

    $this->get('/old?ref=abc')->assertRedirect('/new?ref=abc');
});

it('does not forward the query string when the redirect disables it', function () {
    Redirect::make()->source('/old')->destination('/new')->site('default')->preserveQueryString(false)->save();

    $this->get('/old?ref=abc')->assertRedirect('/new');
});

it('merges the query string into a destination that already has one', function () {
    Redirect::make()->source('/old')->destination('/new?ref=internal')->site('default')->save();

    $this->get('/old?utm=x')->assertRedirect('/new?ref=internal&utm=x');
});

it('appends the query string before a destination fragment', function () {
    Redirect::make()->source('/old')->destination('/new#section')->site('default')->save();

    $this->get('/old?ref=abc')->assertRedirect('/new?ref=abc#section');
});

it('returns 410 for a gone rule', function () {
    Redirect::make()->source('/gone')->responseCode(ResponseCode::Gone)->site('default')->save();

    $this->get('/gone')->assertStatus(410);
});

it('leaves an unmatched 404 alone', function () {
    $this->get('/genuinely-missing')->assertNotFound();
});

it('does not redirect a rule that points to its own path', function () {
    Redirect::make()->source('/old')->destination('/old')->site('default')->save();

    $this->get('/old')->assertNotFound();
});

it('still redirects to an external url with the same path', function () {
    Redirect::make()->source('/old')->destination('https://external.test/old')->site('default')->save();

    $this->get('/old')->assertRedirect('https://external.test/old');
});

it('does nothing when the feature is disabled', function () {
    config(['advanced-seo.redirects.enabled' => false]);

    Redirect::make()->source('/old')->destination('/new')->site('default')->save();

    $this->get('/old')->assertNotFound();
});

it('matches and redirects on a url-prefixed site', function () {
    Site::setSites([
        'default' => ['name' => 'Default', 'url' => '/', 'locale' => 'en'],
        'fr' => ['name' => 'French', 'url' => '/fr/', 'locale' => 'fr'],
    ]);

    Redirect::make()->source('/old')->destination('/new')->site('fr')->save();

    $this->get('/fr/old')->assertRedirect('/fr/new');
});

it('does not redirect non-GET requests', function () {
    Redirect::make()->source('/old')->destination('/new')->site('default')->save();

    $this->post('/old')->assertNotFound();
});

it('records a hit when a redirect fires and hit logging is enabled', function () {
    config(['advanced-seo.redirects.hits.enabled' => true]);
    Queue::fake();

    Redirect::make()->id('r1')->source('/old')->destination('/new')->site('default')->save();

    $this->get('/old')->assertRedirect('/new');

    Queue::assertPushed(RecordRedirectHitJob::class, fn ($job) => $job->redirect === 'r1');
});

it('records a hit for a gone redirect', function () {
    config(['advanced-seo.redirects.hits.enabled' => true]);
    Queue::fake();

    Redirect::make()->id('g1')->source('/gone')->responseCode(ResponseCode::Gone)->site('default')->save();

    $this->get('/gone')->assertStatus(410);

    Queue::assertPushed(RecordRedirectHitJob::class, fn ($job) => $job->redirect === 'g1');
});

it('does not record a hit for a self-referential redirect', function () {
    config(['advanced-seo.redirects.hits.enabled' => true]);
    Queue::fake();

    Redirect::make()->id('s1')->source('/old')->destination('/old')->site('default')->save();

    $this->get('/old');

    Queue::assertNotPushed(RecordRedirectHitJob::class);
});

it('does not record a hit when hit logging is disabled', function () {
    config(['advanced-seo.redirects.hits.enabled' => false]);
    Queue::fake();

    Redirect::make()->id('r1')->source('/old')->destination('/new')->site('default')->save();

    $this->get('/old')->assertRedirect('/new');

    Queue::assertNotPushed(RecordRedirectHitJob::class);
});

it('records an error for an unmatched 404 when error logging is enabled', function () {
    config(['advanced-seo.redirects.errors.enabled' => true]);
    Queue::fake();

    $this->get('/Missing-Page')->assertNotFound();

    Queue::assertPushed(RecordRedirectErrorJob::class, function ($job) {
        return $job->url === '/missing-page' && $job->site === 'default';
    });
});

it('does not record an error when a redirect matches', function () {
    config(['advanced-seo.redirects.errors.enabled' => true]);
    Queue::fake();

    Redirect::make()->source('/old')->destination('/new')->site('default')->save();

    $this->get('/old')->assertRedirect('/new');

    Queue::assertNotPushed(RecordRedirectErrorJob::class);
});

it('does not record an error when error logging is disabled', function () {
    config(['advanced-seo.redirects.errors.enabled' => false]);
    Queue::fake();

    $this->get('/missing')->assertNotFound();

    Queue::assertNotPushed(RecordRedirectErrorJob::class);
});

it('does not record an error for an ignored path', function () {
    config([
        'advanced-seo.redirects.errors.enabled' => true,
        'advanced-seo.redirects.errors.ignore' => ['#\.php$#'],
    ]);
    Queue::fake();

    $this->get('/wp-login.php')->assertNotFound();

    Queue::assertNotPushed(RecordRedirectErrorJob::class);
});
