<?php

namespace Aerni\AdvancedSeo\Http\Controllers\Cp;

use Inertia\Inertia;
use Inertia\Response;
use Statamic\Facades\User;
use Illuminate\Support\Collection;
use Aerni\AdvancedSeo\Models\Defaults;
use Aerni\AdvancedSeo\Contracts\SeoDefaultSet;
use Statamic\Http\Controllers\CP\CpController;
use Statamic\Exceptions\AuthorizationException;

class DashboardController extends CpController
{
    public function __invoke(): Response
    {
        $defaults = $this->defaults();

        throw_unless($defaults->isNotEmpty(), new AuthorizationException());

        return Inertia::render('advanced-seo::Dashboard', [
            'defaults' => $defaults,
        ]);
    }

    protected function defaults(): Collection
    {
        return Defaults::enabled()
            ->groupBy('type')
            ->filter(fn ($defaults, $type) => User::current()->can('viewAny', [SeoDefaultSet::class, $type]))
            ->map(fn ($defaults, $type) =>  [
                'type' => $type,
                'title' => ucfirst($type),
                'route' => cp_route("advanced-seo.{$type}.index"),
                'icon' => $type === 'site' ? 'web' : $defaults->first()['icon'],
            ])
            ->values();
    }
}
