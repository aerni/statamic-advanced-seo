<?php

namespace Aerni\AdvancedSeo\Http\Controllers\Cp;

use Aerni\AdvancedSeo\Data\SeoVariables;
use Aerni\AdvancedSeo\Traits\ValidateType;
use Illuminate\View\View;
use Statamic\Http\Controllers\CP\CpController;

class OverviewController extends CpController
{
    use ValidateType;

    // TODO: This should probably be put in a repository.
    protected array $allowedTypes = ['site', 'content'];

    public function index(): View
    {
        $this->authorize('index', SeoVariables::class);

        return view('advanced-seo::cp.index');
    }

    public function show(string $type): View
    {
        $this->authorize($type . 'DefaultsIndex', SeoVariables::class);

        if (! $this->isValidType($type)) {
            return $this->pageNotFound();
        };

        return view("advanced-seo::cp.{$type}_index");
    }
}
