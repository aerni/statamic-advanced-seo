<?php

use Aerni\AdvancedSeo\Support\ContentExtractor;
use Statamic\Facades\Blueprint;
use Statamic\Facades\Fieldset;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

uses(PreventsSavingStacheItemsToDisk::class);

afterEach(function () {
    Fieldset::find('imported_set')?->delete();
    Fieldset::find('linked_set')?->delete();
});

function extractContent(array $content, $blueprint)
{
    return (new ContentExtractor($blueprint, $content))->run();
}

// --- top-level fields ---

it('extracts text from a top-level text field', function () {
    $blueprint = Blueprint::make()->setContents(['fields' => [
        ['handle' => 'title', 'field' => ['type' => 'text']],
    ]]);

    expect(extractContent(['title' => 'My Page Title'], $blueprint)->get('title'))->toBe('My Page Title');
});

it('ignores non-string content field values', function () {
    $blueprint = Blueprint::make()->setContents(['fields' => [
        ['handle' => 'title', 'field' => ['type' => 'text']],
    ]]);

    expect(extractContent(['title' => ['not' => 'a string']], $blueprint))->not->toHaveKey('title');
});

// --- nested / recursive extraction ---

it('extracts text nested in a replicator set', function () {
    $blueprint = Blueprint::make()->setContents(['fields' => [
        ['handle' => 'blocks', 'field' => [
            'type' => 'replicator',
            'sets' => [
                'content' => ['fields' => [
                    ['handle' => 'paragraph', 'field' => ['type' => 'textarea']],
                ]],
            ],
        ]],
    ]]);

    $content = extractContent([
        'blocks' => [
            ['type' => 'content', '_id' => '1', 'paragraph' => 'REPLICATOR_MARKER_TEXT'],
        ],
    ], $blueprint);

    expect($content->get('blocks'))->toContain('REPLICATOR_MARKER_TEXT');
});

it('extracts text nested in a grid row', function () {
    $blueprint = Blueprint::make()->setContents(['fields' => [
        ['handle' => 'rows', 'field' => [
            'type' => 'grid',
            'fields' => [
                ['handle' => 'cell', 'field' => ['type' => 'text']],
            ],
        ]],
    ]]);

    $content = extractContent([
        'rows' => [
            ['cell' => 'GRID_MARKER_TEXT'],
        ],
    ], $blueprint);

    expect($content->get('rows'))->toContain('GRID_MARKER_TEXT');
});

it('extracts text nested in a group', function () {
    $blueprint = Blueprint::make()->setContents(['fields' => [
        ['handle' => 'meta', 'field' => [
            'type' => 'group',
            'fields' => [
                ['handle' => 'intro', 'field' => ['type' => 'text']],
            ],
        ]],
    ]]);

    $content = extractContent([
        'meta' => ['intro' => 'GROUP_MARKER_TEXT'],
    ], $blueprint);

    expect($content->get('meta'))->toContain('GROUP_MARKER_TEXT');
});

it('extracts bard prose and text nested in bard sets', function () {
    $blueprint = Blueprint::make()->setContents(['fields' => [
        ['handle' => 'article', 'field' => [
            'type' => 'bard',
            'sets' => [
                'callout' => ['fields' => [
                    ['handle' => 'message', 'field' => ['type' => 'text']],
                ]],
            ],
        ]],
    ]]);

    $content = extractContent([
        'article' => [
            ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'BARD_PROSE_MARKER']]],
            ['type' => 'set', 'attrs' => ['id' => '1', 'values' => ['type' => 'callout', 'message' => 'BARD_SET_MARKER']]],
        ],
    ], $blueprint);

    expect($content->get('article'))->toContain('BARD_PROSE_MARKER')
        ->and($content->get('article'))->toContain('BARD_SET_MARKER');
});

it('extracts text from an imported fieldset', function () {
    Fieldset::make('imported_set')->setContents(['fields' => [
        ['handle' => 'tagline', 'field' => ['type' => 'text']],
    ]])->save();

    $blueprint = Blueprint::make()->setContents(['fields' => [
        ['import' => 'imported_set'],
    ]]);

    $content = extractContent([
        'tagline' => 'FIELDSET_IMPORT_MARKER',
    ], $blueprint);

    expect($content->get('tagline'))->toBe('FIELDSET_IMPORT_MARKER');
});

it('extracts text from a single linked fieldset field', function () {
    Fieldset::make('linked_set')->setContents(['fields' => [
        ['handle' => 'tagline', 'field' => ['type' => 'text']],
    ]])->save();

    $blueprint = Blueprint::make()->setContents(['fields' => [
        ['handle' => 'tagline', 'field' => 'linked_set.tagline'],
    ]]);

    $content = extractContent([
        'tagline' => 'FIELDSET_LINK_MARKER',
    ], $blueprint);

    expect($content->get('tagline'))->toBe('FIELDSET_LINK_MARKER');
});

it('extracts text from a container nested inside another container', function () {
    $blueprint = Blueprint::make()->setContents(['fields' => [
        [
            'handle' => 'blocks',
            'field' => [
                'type' => 'replicator',
                'sets' => [
                    'wrapper' => [
                        'fields' => [
                            [
                                'handle' => 'meta',
                                'field' => [
                                    'type' => 'group',
                                    'fields' => [
                                        ['handle' => 'deep', 'field' => ['type' => 'text']],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ]]);

    $content = extractContent([
        'blocks' => [
            ['type' => 'wrapper', '_id' => '1', 'meta' => ['deep' => 'NESTED_MARKER_TEXT']],
        ],
    ], $blueprint);

    expect($content->get('blocks'))->toContain('NESTED_MARKER_TEXT');
});

it('excludes disabled replicator rows', function () {
    $blueprint = Blueprint::make()->setContents(['fields' => [
        ['handle' => 'blocks', 'field' => [
            'type' => 'replicator',
            'sets' => [
                'content' => ['fields' => [
                    ['handle' => 'paragraph', 'field' => ['type' => 'textarea']],
                ]],
            ],
        ]],
    ]]);

    $content = extractContent([
        'blocks' => [
            ['type' => 'content', '_id' => '1', 'enabled' => false, 'paragraph' => 'DISABLED_MARKER'],
            ['type' => 'content', '_id' => '2', 'enabled' => true, 'paragraph' => 'ENABLED_MARKER'],
        ],
    ], $blueprint);

    expect($content->get('blocks'))->toContain('ENABLED_MARKER')
        ->and($content->get('blocks'))->not->toContain('DISABLED_MARKER');
});

it('excludes disabled bard sets', function () {
    $blueprint = Blueprint::make()->setContents(['fields' => [
        ['handle' => 'article', 'field' => [
            'type' => 'bard',
            'sets' => [
                'callout' => ['fields' => [
                    ['handle' => 'message', 'field' => ['type' => 'text']],
                ]],
            ],
        ]],
    ]]);

    $content = extractContent([
        'article' => [
            ['type' => 'set', 'attrs' => ['id' => '1', 'enabled' => false, 'values' => ['type' => 'callout', 'message' => 'DISABLED_MARKER']]],
            ['type' => 'set', 'attrs' => ['id' => '2', 'enabled' => true, 'values' => ['type' => 'callout', 'message' => 'ENABLED_MARKER']]],
        ],
    ], $blueprint);

    expect($content->get('article'))->toContain('ENABLED_MARKER')
        ->and($content->get('article'))->not->toContain('DISABLED_MARKER');
});

it('ignores content referencing an unknown set type', function () {
    $blueprint = Blueprint::make()->setContents(['fields' => [
        ['handle' => 'blocks', 'field' => [
            'type' => 'replicator',
            'sets' => [
                'content' => ['fields' => [
                    ['handle' => 'paragraph', 'field' => ['type' => 'textarea']],
                ]],
            ],
        ]],
    ]]);

    // The set type was renamed or removed from the blueprint, but old content still references it.
    $content = extractContent([
        'blocks' => [
            ['type' => 'removed', '_id' => '1', 'paragraph' => 'STALE_MARKER'],
        ],
    ], $blueprint);

    expect($content)->not->toHaveKey('blocks');
});
