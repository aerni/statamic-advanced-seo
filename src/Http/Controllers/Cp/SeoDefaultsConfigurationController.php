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

        $setSites = $set->sites();

        throw_unless($setSites->count() > 1, new NotFoundHttpException);

        $request->validate([
            'sites' => 'required|array',
            'sites.*.handle' => 'required|string',
            'sites.*.origin' => 'nullable|string',
        ]);

        if ($request->collect('sites')->map->origin->filter()->count() == count($request->sites)) {
            throw ValidationException::withMessages([
                'sites' => __('statamic::validation.one_site_without_origin'),
            ]);
        }

        $request->collect('sites')
            ->each(fn ($site) => $set->in($site['handle'])->origin($site['origin']));

        $set->save();
    }

    protected function type(): string
    {
        $segments = request()->segments();
        $key = array_search('advanced-seo', $segments) + 1;

        return $segments[$key];
    }
}
