<?php

namespace Aerni\AdvancedSeo\Eloquent;

use Aerni\AdvancedSeo\Contracts\SeoSetConfig;
use Aerni\AdvancedSeo\Contracts\SeoSetConfigRepository as Contract;
use Illuminate\Support\Collection;
use Statamic\Facades\Blink;

class SeoSetConfigRepository implements Contract
{
    public function make(): SeoSetConfig
    {
        return app(SeoSetConfig::class);
    }

    public function find(string $id): ?SeoSetConfig
    {
        return Blink::once("advanced-seo.eloquent.set.config.{$id}", function () use ($id) {
            [$type, $handle] = explode('::', $id);

            $model = app('statamic.eloquent.seo_set_config.model')::query()
                ->where('type', $type)
                ->where('handle', $handle)
                ->first();

            if (! $model) {
                return null;
            }

            return app(SeoSetConfig::class)::fromModel($model);
        });
    }

    public function findOrMake(string $id): SeoSetConfig
    {
        return $this->find($id) ?? $this->make();
    }

    public function all(): Collection
    {
        return Blink::once('advanced-seo.eloquent.set.configs', function () {
            return app('statamic.eloquent.seo_set_config.model')::all()
                ->map(fn ($model) => app(SeoSetConfig::class)::fromModel($model));
        });
    }

    public function save(SeoSetConfig $config): void
    {
        $model = $config->toModel();

        $model->save();

        $config->model($model->fresh());

        $this->flushBlink($config);
    }

    public function delete(SeoSetConfig $config): void
    {
        $config->model()->delete();

        $this->flushBlink($config);
    }

    protected function flushBlink(SeoSetConfig $config): void
    {
        Blink::forget('advanced-seo.eloquent.set.configs');
        Blink::forget("advanced-seo.eloquent.set.config.{$config->id()}");
    }

    public static function bindings(): array
    {
        return [
            \Aerni\AdvancedSeo\Contracts\SeoSetConfig::class => \Aerni\AdvancedSeo\Eloquent\SeoSetConfig::class,
        ];
    }
}
