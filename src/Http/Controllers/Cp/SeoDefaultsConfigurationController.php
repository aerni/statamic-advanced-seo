<?php

namespace Aerni\AdvancedSeo\Http\Controllers\Cp;

use Aerni\AdvancedSeo\Data\SeoVariables;
use Aerni\AdvancedSeo\Models\Defaults;
use Illuminate\Http\Request;
use Statamic\Exceptions\NotFoundHttpException;
use Statamic\Exceptions\ValidationException;
use Statamic\Facades\Site;
use Statamic\Http\Controllers\CP\CpController;

class SeoDefaultsConfigurationController extends CpController
{
    public function edit(Request $request, string $handle)
    {
        $defaults = Defaults::firstWhere('id', "{$this->type()}::{$handle}");

        $set = $defaults['set'];

        $this->authorize('edit', [SeoVariables::class, $set]);

        $setSites = $set->sites();

        throw_unless($setSites->count() > 1, new NotFoundHttpException);

        return [
            'sites' => Site::all()
                ->filter(fn ($site, $handle) => $setSites->contains($handle))
                ->map(fn ($site) => [
                    'name' => $site->name(),
                    'handle' => $site->handle(),
                    'origin' => $set->in($site->handle())->origin()?->locale(),
                ])
                ->values(),
        ];
    }

    public function update(Request $request, string $handle): void
    {
        $defaults = Defaults::firstWhere('id', "{$this->type()}::{$handle}");

        $set = $defaults['set'];

        $this->authorize('edit', [SeoVariables::class, $set]);

        throw_unless($set->sites()->count() > 1, new NotFoundHttpException);

        $this->validateSiteOrigins($request);

        $request->collect('sites')
            ->each(fn ($site) => $set->in($site['handle'])->origin($site['origin']));

        $set->save();
    }

    protected function validateSiteOrigins(Request $request): void
    {
        $request->validate([
            'sites' => 'required|array',
            'sites.*.handle' => 'required|string',
            'sites.*.origin' => 'nullable|string',
        ]);

        $sites = $request->collect('sites');

        // At least one site must not have an origin
        if ($sites->map->origin->filter()->count() == count($request->sites)) {
            throw ValidationException::withMessages([
                'sites' => __('statamic::validation.one_site_without_origin'),
            ]);
        }

        // Check for circular origin dependencies
        $originMap = $sites->pluck('origin', 'handle')->filter()->toArray();

        foreach ($originMap as $site => $origin) {
            $visited = [$site];
            $current = $origin;

            while ($current !== null) {
                if (in_array($current, $visited)) {
                    throw ValidationException::withMessages([
                        'sites' => __('Circular site origin dependencies are not allowed.'),
                    ]);
                }

                $visited[] = $current;
                $current = $originMap[$current] ?? null;
            }
        }
    }

    protected function type(): string
    {
        $segments = request()->segments();
        $key = array_search('advanced-seo', $segments) + 1;

        return $segments[$key];
    }
}
