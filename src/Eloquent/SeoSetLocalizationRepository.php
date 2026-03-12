<?php

namespace Aerni\AdvancedSeo\Eloquent;

use Aerni\AdvancedSeo\Contracts\SeoSetLocalization;
use Aerni\AdvancedSeo\Contracts\SeoSetLocalizationRepository as Contract;
use Illuminate\Support\Collection;
use Statamic\Facades\Blink;

class SeoSetLocalizationRepository implements Contract
{
    public function make(): SeoSetLocalization
    {
        return app(SeoSetLocalization::class);
    }

    public function find(string $id): ?SeoSetLocalization
    {
        return Blink::once("advanced-seo.eloquent.set.localization.{$id}", function () use ($id) {
            [$type, $handle, $locale] = explode('::', $id);

            $model = app('statamic.eloquent.seo_set_localization.model')::query()
                ->where('type', $type)
                ->where('handle', $handle)
                ->where('locale', $locale)
                ->first();

            if (! $model) {
                return null;
            }

            return app(SeoSetLocalization::class)::fromModel($model);
        });
    }

    public function all(): Collection
    {
        return Blink::once('advanced-seo.eloquent.set.localizations', function () {
            return app('statamic.eloquent.seo_set_localization.model')::all()
                ->map(fn ($model) => app(SeoSetLocalization::class)::fromModel($model));
        });
    }

    public function whereSeoSet(string $id): Collection
    {
        return Blink::once("advanced-seo.eloquent.set.localizations.{$id}", function () use ($id) {
            [$type, $handle] = explode('::', $id);

            return app('statamic.eloquent.seo_set_localization.model')::query()
                ->where('type', $type)
                ->where('handle', $handle)
                ->get()
                ->map(fn ($model) => app(SeoSetLocalization::class)::fromModel($model));
        });
    }

    public function save(SeoSetLocalization $localization): void
    {
        $model = $localization->toModel();

        $model->save();

        $localization->model($model->fresh());

        $this->flushBlink($localization);
    }

    public function delete(SeoSetLocalization $localization): void
    {
        $localization->model()->delete();

        $this->flushBlink($localization);
    }

    protected function flushBlink(SeoSetLocalization $localization): void
    {
        Blink::forget('advanced-seo.eloquent.set.localizations');
        Blink::forget("advanced-seo.eloquent.set.localizations.{$localization->type()}::{$localization->handle()}");
        Blink::forget("advanced-seo.eloquent.set.localization.{$localization->id()}");
    }

    public static function bindings(): array
    {
        return [
            SeoSetLocalization::class => \Aerni\AdvancedSeo\Eloquent\SeoSetLocalization::class,
        ];
    }
}
