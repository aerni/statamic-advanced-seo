<?php

namespace Aerni\AdvancedSeo\Contracts;

interface SeoRepository
{
    public function make();

    public function all();

    public function find($id);

    public function query();

    public function save($seo);

    public function delete($seo);
}
