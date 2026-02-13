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
        $general = Seo::find('site::general')->in($context->site);

        return [
            'siteName' => $general->site_name,
            'domain' => parse_url($general->site()->absoluteUrl(), PHP_URL_HOST),
            'titleSeparator' => $general->title_separator->value(),
            'favicon' => Seo::find('site::favicons')->in($context->site)->favicon_svg?->url(),
            'uri' => $parent->uri(),
            'breadcrumbs' => ResolveBreadcrumbs::handle(
                str($parent->uri())->explode('/')->filter()->values()->all(),
                $context->site,
            )->pluck('title'),
        ];
    }
}
