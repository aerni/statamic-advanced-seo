<?php

use Aerni\AdvancedSeo\Cascades\CascadeComposer;
use Aerni\AdvancedSeo\Facades\Seo;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Statamic\Facades\Collection;
use Statamic\Facades\Site;
use Statamic\Facades\Taxonomy;
use Statamic\Tags\Context;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

uses(PreventsSavingStacheItemsToDisk::class);

/*
 * Characterization tests for CascadeComposer::shouldProcessCascade.
 *
 * The gate is a pure function of (request state, view data) → bool. We use
 * Laravel's HTTP test helpers ($this->get) to bind realistic Request and Route
 * state to the container (matching Statamic's own testing style in
 * StatamicTest::isCpRoute), then invoke the protected method via reflection
 * because the gate is not a natural public surface and exercising it through
 * full view rendering would couple tests to cascade output semantics we're
 * not trying to verify here.
 */

beforeEach(function () {
    Site::setSites([
        'english' => ['name' => 'English', 'url' => '/', 'locale' => 'en'],
    ]);

    Collection::make('pages')->routes('/{slug}')->sites(['english'])->saveQuietly();
    Collection::make('articles')->sites(['english'])->saveQuietly();

    Taxonomy::make('tags')->sites(['english'])->saveQuietly();
    Taxonomy::make('categories')->sites(['english'])->saveQuietly();
});

function shouldProcess(array $viewData): bool
{
    $composer = new CascadeComposer;
    $context = new Context($viewData);

    $method = new ReflectionMethod($composer, 'shouldProcessCascade');
    $method->setAccessible(true);

    return $method->invoke($composer, $context);
}

// -----------------------------------------------------------------------------
// Early exits
// -----------------------------------------------------------------------------

it('does not process the cascade on a CP route', function () {
    $this->get('/cp/collections/pages/entries/123');

    expect(shouldProcess(['is_entry' => true, 'collection' => Collection::findByHandle('pages')]))->toBeFalse();
});

it('does not process the cascade when seo is already present in the view data', function () {
    $this->get('/');

    expect(shouldProcess(['is_entry' => true, 'collection' => Collection::findByHandle('pages'), 'seo' => 'anything']))->toBeFalse();
});

// -----------------------------------------------------------------------------
// Entry views
// -----------------------------------------------------------------------------

it('processes the cascade on an entry view when the collection SeoSet is enabled', function () {
    $this->get('/about');

    expect(shouldProcess(['is_entry' => true, 'collection' => Collection::findByHandle('pages')]))->toBeTrue();
});

it('does not process the cascade on an entry view when the collection SeoSet is disabled', function () {
    Seo::find('collections::pages')->config()->enabled(false)->save();
    $this->get('/about');

    expect(shouldProcess(['is_entry' => true, 'collection' => Collection::findByHandle('pages')]))->toBeFalse();
});

// -----------------------------------------------------------------------------
// Term views
// -----------------------------------------------------------------------------

it('processes the cascade on a term view when the taxonomy SeoSet is enabled', function () {
    $this->get('/tags/foo');

    expect(shouldProcess(['is_term' => true, 'taxonomy' => Taxonomy::findByHandle('tags')]))->toBeTrue();
});

it('does not process the cascade on a term view when the taxonomy SeoSet is disabled', function () {
    Seo::find('taxonomies::categories')->config()->enabled(false)->save();
    $this->get('/categories/foo');

    expect(shouldProcess(['is_term' => true, 'taxonomy' => Taxonomy::findByHandle('categories')]))->toBeFalse();
});

// -----------------------------------------------------------------------------
// Taxonomy index (listing) views
// -----------------------------------------------------------------------------

it('processes the cascade on a taxonomy listing view when the taxonomy SeoSet is enabled', function () {
    $this->get('/tags');

    $taxonomy = Taxonomy::findByHandle('tags');

    expect(shouldProcess([
        'terms' => $taxonomy->queryTerms(),
        'handle' => $taxonomy->toAugmentedArray()['handle'] ?? $taxonomy,
    ]))->toBeTrue();
});

it('does not process the cascade on a taxonomy listing view when the taxonomy SeoSet is disabled', function () {
    Seo::find('taxonomies::categories')->config()->enabled(false)->save();
    $this->get('/categories');

    $taxonomy = Taxonomy::findByHandle('categories');

    expect(shouldProcess([
        'terms' => $taxonomy->queryTerms(),
        'handle' => $taxonomy->toAugmentedArray()['handle'] ?? $taxonomy,
    ]))->toBeFalse();
});

// -----------------------------------------------------------------------------
// Custom routes (Pro + opt-in)
//
// Statamic's FrontendController intercepts all frontend paths in test mode,
// so we can't register a real custom route via Route::get(). Instead we
// construct a Request with a route resolver that returns a route with a
// non-allowed controller action — the same state a real custom route would
// produce at runtime.
// -----------------------------------------------------------------------------

function fakeCustomRouteRequest(string $path): void
{
    $request = Request::create('/'.ltrim($path, '/'));
    $request->setRouteResolver(function () use ($request, $path) {
        $route = new Route(['GET'], '/'.ltrim($path, '/'), ['controller' => 'App\\Http\\CustomController@index']);
        $route->bind($request);

        return $route;
    });
    app()->instance('request', $request);
}

it('does not process the cascade on a custom route without Pro', function () {
    useFreeEdition();
    fakeCustomRouteRequest('/custom');

    expect(shouldProcess(['seo_enabled' => true]))->toBeFalse();
});

it('does not process the cascade on a custom route with Pro but no opt-in', function () {
    fakeCustomRouteRequest('/custom');

    expect(shouldProcess(['seo_enabled' => false]))->toBeFalse();
});

it('processes the cascade on a custom route with Pro and explicit opt-in', function () {
    fakeCustomRouteRequest('/custom');

    expect(shouldProcess(['seo_enabled' => true]))->toBeTrue();
});

// -----------------------------------------------------------------------------
// Social image routes
//
// The social image controller is invokable, so Laravel stores its action as
// the bare class name (no `@method` suffix). Without the bare-class entry in
// Helpers::isCustomRoute()'s allowlist, social image requests get classified
// as "custom routes" and short-circuit, leaving `{{ seo:... }}` tags empty in
// the rendered templates.
// -----------------------------------------------------------------------------

it('processes the cascade on a social image route (invokable controller)', function () {
    // The controller will 404 on the nonsense id, but the route is still bound
    // to the request — which is all shouldProcess inspects.
    $this->get('/!/advanced-seo/social-images/default/og/xxx/english');

    expect(shouldProcess(['is_entry' => true, 'collection' => Collection::findByHandle('pages')]))->toBeTrue();
});

// -----------------------------------------------------------------------------
// Default-pass behavior
// -----------------------------------------------------------------------------

it('processes the cascade by default when no entry/term/terms markers are set and not a custom route', function () {
    // Hit the Statamic frontend controller path (a non-custom route) with no
    // entry/term/terms markers in view data.
    $this->get('/');

    expect(shouldProcess([]))->toBeTrue();
});
