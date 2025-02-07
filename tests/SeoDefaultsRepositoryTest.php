<?php

namespace Tests;

use Aerni\AdvancedSeo\Data\SeoDefaultSet;
use Aerni\AdvancedSeo\Facades\Seo;
use Aerni\AdvancedSeo\Tests\TestCase;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Test;

class SeoDefaultsRepositoryTest extends TestCase
{
    #[Test]
    public function it_can_find_seo_default(): void
    {
        $this->assertInstanceOf(SeoDefaultSet::class, Seo::find('site', 'general'));
    }

    #[Test]
    public function it_can_get_all_seo_defaults(): void
    {
        $defaults = Seo::all();

        $this->assertInstanceOf(Collection::class, $defaults);
        $this->assertArrayHasKey('collections', $defaults);
        $this->assertArrayHasKey('taxonomies', $defaults);
        $this->assertArrayHasKey('site', $defaults);
    }
}
