<?php

namespace Aerni\AdvancedSeo\Http\Controllers\Cp;

use Aerni\AdvancedSeo\Data\SeoVariables;
use Aerni\AdvancedSeo\Models\Defaults;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;
use Statamic\Exceptions\AuthorizationException;
use Statamic\Facades\User;
use Statamic\Http\Controllers\CP\CpController;

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
            ->filter(fn ($default) => User::current()->can('edit', [SeoVariables::class, $default['set']]))
            ->keyBy('type')
            ->map(fn ($type) =>  [
                'type' => $type['type'],
                'title' => ucfirst($type['type']),
                'route' => cp_route("advanced-seo.{$type['type']}.index"),
                'icon' => $type['type'] === 'site' ? 'web' : $type['icon'],
            ])
            ->values();
    }
}
