<?php

namespace Aerni\AdvancedSeo\Fieldtypes;

use Aerni\AdvancedSeo\Actions\GetAuthorizedSites;
use Aerni\AdvancedSeo\Facades\Seo;
use Aerni\AdvancedSeo\SeoSets\SeoSet;
use Illuminate\Support\Collection;
use Statamic\Facades\Site;
use Statamic\Facades\User;
use Statamic\Fields\Fieldtype;

class HreflangXDefaultFieldtype extends Fieldtype
{
    protected $selectable = false;

    public function preload(): array
    {
        $authorized = $this->authorizedHandles();

        return [
            'options' => Site::all()->map(function ($site) use ($authorized) {
                $disabled = ! $authorized->contains($site->handle());

                return [
                    'value' => $site->handle(),
                    'label' => $site->name(),
                    'disabled' => $disabled,
                    ...($disabled ? ['icon' => 'padlock-locked'] : []),
                ];
            })->values()->all(),
        ];
    }

    /**
     * A site the user cannot access stays in place unless they pick a site they can access.
     * Resubmitting the current value must not replace an earlier save.
     */
    public function process($value)
    {
        if (is_string($value) && $this->authorizedHandles()->contains($value)) {
            return $value;
        }

        $stored = $this->storedValue();

        if (is_string($value) && $value === $stored && Site::get($value)) {
            return $stored;
        }

        return $this->fallbackValue();
    }

    protected function authorizedHandles(): Collection
    {
        $set = $this->seoSet();

        if (! $set || ! User::current()) {
            return Site::all()->map->handle();
        }

        return GetAuthorizedSites::handle($set)->map->handle();
    }

    protected function storedValue(): ?string
    {
        $value = $this->seoSet()?->config()->get('hreflang_x_default');

        return is_string($value) && $value !== '' ? $value : null;
    }

    protected function fallbackValue(): string
    {
        $stored = $this->storedValue();

        if (is_string($stored) && Site::get($stored)) {
            return $stored;
        }

        return Site::default()->handle();
    }

    protected function seoSet(): ?SeoSet
    {
        $parent = $this->field()?->parent();

        if ($parent instanceof SeoSet) {
            return $parent;
        }

        return Seo::find('site::defaults');
    }
}
