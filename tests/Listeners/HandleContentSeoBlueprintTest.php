<?php

use Aerni\AdvancedSeo\Facades\Seo;
use Statamic\Facades\Blink;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Statamic\Facades\Site;
use Statamic\Facades\Taxonomy;
use Statamic\Facades\Term;
use Statamic\Facades\User;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

uses(PreventsSavingStacheItemsToDisk::class);

/*
 * Characterization tests for HandleContentSeoBlueprint::shouldExtendBlueprint.
 *
 * Pinning down current behavior before a planned refactor.
 *
 * Approach: integration tests using Laravel's HTTP test helpers ($this->get()).
 * Each test makes a real request to the relevant URL — this binds the right
 * Request and Route to the container so Statamic::isCpRoute() and the
 * listener's route-name matcher see realistic state. We then resolve the
 * blueprint via the public API ($collection->entryBlueprint() /
 * $taxonomy->termBlueprint()), which fires the BlueprintFound event and runs
 * our auto-registered listener. Finally we assert whether the SEO tab was
 * added to the resulting blueprint contents.
 *
 * Entry vs term coverage: the gate decision is shared — both handlers call
 * the same extendBlueprint() method. Entry tests cover every gate branch.
 * Term tests are a deliberate smoke subset that verify the type-specific
 * wiring (ContextResolver's TermBlueprintFound branch and the route-name
 * mapping for taxonomies/terms), not the shared gate logic.
 */

beforeEach(function () {
    Site::setSites([
        'english' => ['name' => 'English', 'url' => '/', 'locale' => 'en'],
        'german' => ['name' => 'German', 'url' => '/de', 'locale' => 'de'],
    ]);

    Collection::make('pages')
        ->routes('/{slug}')
        ->sites(['english', 'german'])
        ->saveQuietly();

    Collection::make('articles')->sites(['english', 'german'])->saveQuietly();

    Taxonomy::make('tags')->sites(['english', 'german'])->saveQuietly();
    Taxonomy::make('categories')->sites(['english', 'german'])->saveQuietly();

    $this->superUser = User::make()->makeSuper()->save();
});

function entryBlueprintIsExtended(string $collection): bool
{
    return array_key_exists('seo', Collection::findByHandle($collection)->entryBlueprint()->contents()['tabs']);
}

function termBlueprintIsExtended(string $taxonomy): bool
{
    return array_key_exists('seo', Taxonomy::findByHandle($taxonomy)->termBlueprint()->contents()['tabs']);
}

function entrySeoFieldsAreAllHidden(string $collection): bool
{
    $sections = Collection::findByHandle($collection)->entryBlueprint()->contents()['tabs']['seo']['sections'] ?? [];

    foreach ($sections as $section) {
        foreach ($section['fields'] ?? [] as $field) {
            if (($field['field']['visibility'] ?? null) !== 'hidden') {
                return false;
            }
        }
    }

    return true;
}

function entrySeoFieldHandles(string $collection): array
{
    $sections = Collection::findByHandle($collection)->entryBlueprint()->contents()['tabs']['seo']['sections'] ?? [];
    $handles = [];

    foreach ($sections as $section) {
        foreach ($section['fields'] ?? [] as $field) {
            $handles[] = $field['handle'];
        }
    }

    return $handles;
}

function entrySeoFieldsHaveNoValidateRules(string $collection): bool
{
    $sections = Collection::findByHandle($collection)->entryBlueprint()->contents()['tabs']['seo']['sections'] ?? [];

    foreach ($sections as $section) {
        foreach ($section['fields'] ?? [] as $field) {
            if (array_key_exists('validate', $field['field'] ?? [])) {
                return false;
            }
        }
    }

    return true;
}

describe('universal check', function () {
    it('does not extend an entry blueprint when the SeoSet is disabled', function () {
        Seo::find('collections::pages')
            ->config()
            ->enabled(false)
            ->save();

        $this->get('/');

        expect(entryBlueprintIsExtended('pages'))->toBeFalse();
    });
});

describe('frontend requests', function () {
    it('extends an entry blueprint on a frontend request when the SeoSet is enabled', function () {
        $entry = Entry::make()
            ->collection('pages')
            ->locale('english')
            ->slug('about')
            ->data(['title' => 'About']);

        $entry->save();

        $this->get('/about');

        expect(entryBlueprintIsExtended('pages'))->toBeTrue();
    });
});

describe('CP allowed routes', function () {
    it('extends an entry blueprint on the CP model edit route', function () {
        $entry = Entry::make()
            ->collection('pages')
            ->locale('english')
            ->slug('about')
            ->data(['title' => 'About']);

        $entry->save();

        $this->actingAs($this->superUser)->get("/cp/collections/pages/entries/{$entry->id()}");

        expect(entryBlueprintIsExtended('pages'))->toBeTrue();
    });

    it('extends an entry blueprint on the CP create route (locale in path)', function () {
        $this->actingAs($this->superUser)->get('/cp/collections/pages/entries/create/english');

        expect(entryBlueprintIsExtended('pages'))->toBeTrue();
    });

    it('extends an entry blueprint on the CP entry action route', function () {
        $this->actingAs($this->superUser)->post('/cp/collections/pages/entries/actions');

        expect(entryBlueprintIsExtended('pages'))->toBeTrue();
    });

    it('extends an entry blueprint on the AI generate CP route', function () {
        $this->actingAs($this->superUser)->post('/cp/advanced-seo/ai/generate');

        expect(entryBlueprintIsExtended('pages'))->toBeTrue();
    });
});

describe('CP disallowed routes', function () {
    it('does not extend an entry blueprint on the blueprint editor CP route', function () {
        $this->actingAs($this->superUser)->get('/cp/fields/blueprints/collections/pages/page');

        expect(entryBlueprintIsExtended('pages'))->toBeFalse();
    });

    it('does not extend an entry blueprint on a non-AI addon CP route', function () {
        $this->actingAs($this->superUser)->get('/cp/advanced-seo/collections/pages');

        expect(entryBlueprintIsExtended('pages'))->toBeFalse();
    });

    it('does not extend an entry blueprint on an unrelated CP route', function () {
        $this->actingAs($this->superUser)->get('/cp/dashboard');

        expect(entryBlueprintIsExtended('pages'))->toBeFalse();
    });
});

describe('CP editable + permission', function () {
    it('extends but hides all SEO fields in the CP when the SeoSet is not editable', function () {
        $entry = Entry::make()
            ->collection('pages')
            ->locale('english')
            ->slug('about')
            ->data(['title' => 'About']);

        $entry->save();

        Seo::find('collections::pages')
            ->config()
            ->editable(false)
            ->save();

        /*
         * Entry::save() above fires EntryBlueprintFound under the default (frontend)
         * request state, where the listener correctly extends the blueprint. In
         * production, each request starts with a fresh Blink; in tests the cache
         * bleeds. Flushing here makes the CP GET's blueprint event fire fresh —
         * this time under CP state with editable=false.
         */
        Blink::flush();

        $this->actingAs($this->superUser)->get("/cp/collections/pages/entries/{$entry->id()}");

        // Fields are still present so automated/read paths (sitemap, social image
        // generation, meta tag rendering) keep working — only the UI disappears.
        expect(entryBlueprintIsExtended('pages'))->toBeTrue();
        expect(entrySeoFieldsAreAllHidden('pages'))->toBeTrue();
        // Sanity check: representative non-feature-gated fields still reach the blueprint.
        expect(entrySeoFieldHandles('pages'))->toContain('seo_title', 'seo_noindex');
        // Validation rules must be stripped so editors can't get stuck unable to save
        // (e.g. required_if on seo_canonical_entry when the user can't edit the field).
        expect(entrySeoFieldsHaveNoValidateRules('pages'))->toBeTrue();
    });

    it('extends but hides all SEO fields in the CP when the user lacks seo.edit-content permission', function () {
        /*
         * A plain user with no roles has no 'edit seo content' / 'edit seo defaults'
         * / 'configure seo' permissions. Under the Pro edition (default in tests),
         * the gate denies access.
         */
        $user = User::make()->save();

        $entry = Entry::make()
            ->collection('pages')
            ->locale('english')
            ->slug('about')
            ->data(['title' => 'About']);

        $entry->save();

        // See the editable-false test above for why Blink::flush() is needed here.
        Blink::flush();

        $this->actingAs($user)->get("/cp/collections/pages/entries/{$entry->id()}");

        expect(entryBlueprintIsExtended('pages'))->toBeTrue();
        expect(entrySeoFieldsAreAllHidden('pages'))->toBeTrue();
        expect(entrySeoFieldsHaveNoValidateRules('pages'))->toBeTrue();
    });

    it('extends an entry blueprint with visible fields on a frontend request when the SeoSet is not editable', function () {
        Seo::find('collections::pages')
            ->config()
            ->editable(false)
            ->save();

        $entry = Entry::make()
            ->collection('pages')
            ->locale('english')
            ->slug('about')
            ->data(['title' => 'About']);

        $entry->save();

        Blink::flush();

        $this->get('/about');

        // On the frontend, editable(false) should not hide fields — hiding is CP-only.
        expect(entryBlueprintIsExtended('pages'))->toBeTrue();
        expect(entrySeoFieldsAreAllHidden('pages'))->toBeFalse();
    });
});

describe('term blueprint', function () {
    it('extends a term blueprint on a frontend request when the SeoSet is enabled', function () {
        Term::make()
            ->taxonomy('tags')
            ->slug('foo')
            ->set('title', 'Foo')
            ->save();

        $this->get('/tags/foo');

        expect(termBlueprintIsExtended('tags'))->toBeTrue();
    });

    it('does not extend a term blueprint when the taxonomy SeoSet is disabled', function () {
        Seo::find('taxonomies::categories')
            ->config()
            ->enabled(false)
            ->save();

        Term::make()
            ->taxonomy('categories')
            ->slug('foo')
            ->set('title', 'Foo')
            ->save();

        $this->get('/categories/foo');

        expect(termBlueprintIsExtended('categories'))->toBeFalse();
    });

    it('extends a term blueprint on the CP term action route', function () {
        $this->actingAs($this->superUser)->post('/cp/taxonomies/tags/terms/actions');

        expect(termBlueprintIsExtended('tags'))->toBeTrue();
    });

    it('does not extend a term blueprint on the blueprint editor CP route', function () {
        $this->actingAs($this->superUser)->get('/cp/fields/blueprints/taxonomies/tags/tag');

        expect(termBlueprintIsExtended('tags'))->toBeFalse();
    });
});
