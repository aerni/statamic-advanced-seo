<?php

namespace Aerni\AdvancedSeo\Fieldtypes;

use Aerni\AdvancedSeo\Actions\ResolveBreadcrumbs;
use Aerni\AdvancedSeo\Context\Context;
use Aerni\AdvancedSeo\Facades\Seo;
use Statamic\Fields\Fieldtype;

class SearchPreviewFieldtype extends Fieldtype
{
    protected static $handle = 'search_preview';

    protected $selectable = false;

    public function preload(): array
    {
        $parent = $this->field()->parent();
        $context = Context::from($parent);
        $defaults = Seo::find('site::defaults')->in($context->site);

        return [
            'siteName' => $defaults->site_name,
            'domain' => parse_url($defaults->site()->absoluteUrl(), PHP_URL_HOST),
            'favicon' => $defaults->favicon_svg?->url(),
            'uri' => $parent->uri(),
            'breadcrumbs' => ResolveBreadcrumbs::handle(
                str($parent->uri())->explode('/')->filter()->values()->all(),
                $context->site,
            )->pluck('title'),
        ];
    }
}
