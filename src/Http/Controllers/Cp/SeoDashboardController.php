<?php

namespace Aerni\AdvancedSeo\Http\Controllers\Cp;

use Aerni\AdvancedSeo\Contracts\SeoDefaultSet;
use Aerni\AdvancedSeo\Data\SeoVariables;
use Aerni\AdvancedSeo\Events\SeoDefaultSetSaved;
use Aerni\AdvancedSeo\Facades\Seo;
use Aerni\AdvancedSeo\Models\Defaults;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Statamic\Exceptions\NotFoundHttpException;
use Statamic\Facades\Site;
use Statamic\Facades\User;
use Statamic\Fields\Blueprint;
use Statamic\Http\Controllers\CP\CpController;

class SeoDashboardController extends CpController
{
    public function __invoke(): Response
    {
        return Inertia::render('advanced-seo::Dashboard', [
            'defaults' => $this->defaults(),
        ]);
    }

    protected function defaults(): Collection
    {
        return Defaults::enabled()
            ->filter(fn ($default) => User::current()->can('view', [SeoVariables::class, $default['set']]))
            ->keyBy('type')
            ->map(function ($type) {
                return [
                    'type' => $type['type'],
                    'title' => ucfirst($type['type']),
                    'route' => cp_route("advanced-seo.{$type['type']}.index"),
                    'icon' => $type['type'] === 'site' ? 'web' : $type['icon'],
                ];
            })
            ->values();
    }
}
