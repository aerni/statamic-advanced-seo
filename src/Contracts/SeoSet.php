<?php

namespace Aerni\AdvancedSeo\Contracts;

use Illuminate\Contracts\Routing\UrlRoutable;

interface SeoSet extends UrlRoutable
{
    public function id(): string;

    public function type(): string;

    public function handle(): string;

    public function title(): string;

    public function icon(): string;
}
