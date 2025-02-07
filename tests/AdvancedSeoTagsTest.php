<?php

namespace Tests;

use Illuminate\Contracts\View\View;
use Aerni\AdvancedSeo\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Aerni\AdvancedSeo\Tags\AdvancedSeoTags;

class AdvancedSeoTagsTest extends TestCase
{
    protected AdvancedSeoTags $tag;

    protected $defaultContext = ['seo' => ['title' => 'Title']];

    protected function setUp(): void
    {
        parent::setUp();

        $this->tag = (new AdvancedSeoTags)->setContext($this->defaultContext);
    }

    #[Test]
    public function it_returns_head_view(): void
    {
        $this->assertInstanceOf(View::class, $this->tag->head());

        $this->tag->setContext([]);

        $this->assertNull($this->tag->head());
    }

    #[Test]
    public function it_returns_body_view(): void
    {
        $this->assertInstanceOf(View::class, $this->tag->body());

        $this->tag->setContext([]);

        $this->assertNull($this->tag->body());
    }
}
