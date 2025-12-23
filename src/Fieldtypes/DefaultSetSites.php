<?php

namespace Aerni\AdvancedSeo\Fieldtypes;

use Closure;
use Statamic\Fields\Fieldtype;
use Aerni\AdvancedSeo\Actions\GetAuthorizedSites;
use Illuminate\Contracts\Validation\ValidationRule;

class DefaultSetSites extends Fieldtype
{
    protected $selectable = false;

    public function preProcess($data): array
    {
        $set = $this->field->parent();

        return GetAuthorizedSites::handle($set)
            ->intersect($set->sites())
            ->map(fn ($site) => [
                'handle' => $site->handle(),
                'label' => $site->name(),
                'origin' => data_get($data, $site->handle()),
            ])
            ->values()
            ->all();
    }

    public function process($data): array
    {
        return collect($data)
            ->mapWithKeys(fn ($data) => [$data['handle'] => $data['origin']])
            ->all();
    }

    public function rules(): array
    {
        return [
            'array',
            $this->requireAtLeastOneRootSite(),
            $this->preventCircularOrigins(),
        ];
    }

    private function requireAtLeastOneRootSite(): ValidationRule
    {
        return new class implements ValidationRule
        {
            public function validate(string $attribute, mixed $value, Closure $fail): void
            {
                // At least one site must not have an origin
                if (collect($value)->map->origin->filter()->count() === count($value)) {
                    $fail(__('statamic::validation.one_site_without_origin'));
                }
            }
        };
    }

    private function preventCircularOrigins(): ValidationRule
    {
        return new class implements ValidationRule
        {
            public function validate(string $attribute, mixed $value, Closure $fail): void
            {
                $sites = collect($value);

                // Build origin map: site handle => origin handle
                $originMap = $sites->pluck('origin', 'handle')->filter()->toArray();

                // Check each site for circular dependencies
                foreach ($originMap as $site => $origin) {
                    $visited = [$site];
                    $current = $origin;

                    while ($current !== null) {
                        if (in_array($current, $visited)) {
                            $fail(__('Circular site origin dependencies are not allowed.'));
                            return;
                        }

                        $visited[] = $current;
                        $current = $originMap[$current] ?? null;
                    }
                }
            }
        };
    }
}
