<?php

use Aerni\AdvancedSeo\Tags\AdvancedSeoTags;
use Illuminate\Contracts\View\View;

beforeEach(function () {
    $this->tag = (new AdvancedSeoTags)->setContext([
        'seo' => [
            'title' => 'Title',
        ],
    ]);
});

it('returns head view', function () {
    expect($this->tag->head())->toBeInstanceOf(View::class);

    $this->tag->setContext([]);

    expect($this->tag->head())->toBeNull();
});

it('returns body view', function () {
    expect($this->tag->body())->toBeInstanceOf(View::class);

    $this->tag->setContext([]);

    expect($this->tag->body())->toBeNull();
});
