<?php

namespace Aerni\AdvancedSeo\Fieldtypes;

use Aerni\AdvancedSeo\Actions\GetAuthorizedSites;
use Aerni\AdvancedSeo\Actions\HasCircularOrigins;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Statamic\Fields\Fieldtype;

class SiteOriginsFieldtype extends Fieldtype
{
    protected $selectable = false;

    public function preProcess($data): array
    {
        $set = $this->field->parent();
        $authorizedSites = GetAuthorizedSites::handle($set)->map->handle();

        return $set->sites()
            ->map(fn ($site) => [
                'handle' => $site->handle(),
                'label' => $site->name(),
                'origin' => data_get($data, $site->handle()),
                'readonly' => ! $authorizedSites->contains($site->handle()),
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
                if (collect($value)->every(fn ($site) => $site['origin'])) {
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
                $origins = collect($value)
                    ->pluck('origin', 'handle')
                    ->all();

                if (HasCircularOrigins::handle($origins)) {
                    $fail(__('Circular site origin dependencies are not allowed.'));
                }
            }
        };
    }
}
