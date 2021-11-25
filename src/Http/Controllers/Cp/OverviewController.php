<?php

namespace Aerni\AdvancedSeo\Http\Controllers\Cp;

use Aerni\AdvancedSeo\Data\SeoVariables;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Statamic\Exceptions\NotFoundHttpException;
use Statamic\Http\Controllers\CP\CpController;

class OverviewController extends CpController
{
    const GROUPS = ['site', 'content'];

    public function index(): View
    {
        $this->authorize('index', SeoVariables::class);

        return view('advanced-seo::cp.index');
    }

    public function show(string $group): View|Response
    {
        throw_unless(in_array($group, self::GROUPS), new NotFoundHttpException);

        $this->authorize($group . 'DefaultsIndex', SeoVariables::class);

        return view("advanced-seo::cp.{$group}_index");
    }
}
