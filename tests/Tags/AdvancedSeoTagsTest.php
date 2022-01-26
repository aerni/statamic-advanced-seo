<?php

namespace Tests\Tags;

use Aerni\AdvancedSeo\Tests\TestCase;
use Aerni\AdvancedSeo\Tags\AdvancedSeoTags;
use Aerni\AdvancedSeo\Tests\PreventSavingStacheItemsToDisk;

class AdvancedSeoTagsTest extends TestCase
{
    protected AdvancedSeoTags $tag;
    protected $context = ['foo' => 'bar'];

    public function setUp(): void
    {
        parent::setUp();

        $this->tag = (new AdvancedSeoTags())->setContext($this->context);
    }

    /** @test */
    public function it_returns_null_from_the_wildcard(): void
    {
        $this->assertNull($this->tag->wildcard());
    }

    /** @test */
    public function it_returns_the_head_view_with_context(): void
    {
        $view = $this->tag->head();

        $this->assertEquals($view->getName(), 'advanced-seo::head');
        $this->assertEquals($view->getData(), $this->context);
    }

    /** @test */
    public function it_returns_the_body_view_with_context(): void
    {
        $view = $this->tag->body();

        $this->assertEquals($view->getName(), 'advanced-seo::body');
        $this->assertEquals($view->getData(), $this->context);
    }
}
