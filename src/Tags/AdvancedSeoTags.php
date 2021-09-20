<?php

namespace Aerni\AdvancedSeo\Tags;

use Statamic\Tags\Tags;
use Aerni\AdvancedSeo\View\Cascade;

class AdvancedSeoTags extends Tags
{
    protected static $handle = 'advanced_seo';

    public function head()
    {
        $data = Cascade::make($this->context)->get();

        return $this->view('advanced-seo::head', $data);
    }

    public function body()
    {
        $data = Cascade::make($this->context)->get();

        return $this->view('advanced-seo::body', $data);
    }

    protected function view(...$args): string
    {
        // Render view.
        $html = view(...$args)->render();

        // Remove new lines.
        $html = str_replace(["\n", "\r"], '', $html);

        // Remove whitespace between elements.
        $html = preg_replace('/(>)\s*(<)/', '$1$2', $html);

        // Add cleaner line breaks.
        $html = preg_replace('/(<[^\/])/', "\n$1", $html);

        return trim($html);
    }
}
