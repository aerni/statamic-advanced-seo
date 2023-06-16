<?php

namespace Tests\Tags;

use Aerni\AdvancedSeo\Tags\AdvancedSeoTags;
use Aerni\AdvancedSeo\Tests\TestCase;

class AdvancedSeoTagsTest extends TestCase
{
    protected AdvancedSeoTags $tag;

    protected $context = ['foo' => 'bar'];

    public function setUp(): void
    {
        parent::setUp();

        $this->tag = (new AdvancedSeoTags())->setContext($this->context);
    }
}
