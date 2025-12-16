<?php

namespace Aerni\AdvancedSeo\Fieldtypes;

use Closure;
use Statamic\Facades\Site;
use Statamic\Fields\Fieldtype;
use Illuminate\Contracts\Validation\ValidationRule;

class OriginFieldtype extends Fieldtype
{
    protected $selectable = false;

    public function preload(): array
    {
        $localization = $this->field->parent();

        return $localization->sites()
            // ->intersect(Site::authorized()) // TODO: Probably need this so users only see sites they are authorized for?
            ->filter(fn ($site) => $site !== $localization->locale())
            ->map(function ($site) {
                $site = Site::get($site);
                return ['value' => $site->handle(), 'label' => $site->name()];
            })
            ->values()
            ->all();
    }

    public function rules(): array
    {
        return [
            $this->preventCircularOrigins(),
        ];
    }

    private function preventCircularOrigins(): ValidationRule
    {
        return new class($this->field) implements ValidationRule
        {
            public function __construct(private $field)
            {
                //
            }

            public function validate(string $attribute, mixed $value, Closure $fail): void
            {
                if (empty($value)) {
                    return;
                }

                if (! $parent = $this->field->parent()) {
                    return;
                }

                $currentLocale = $parent->locale();

                $originMap = $parent->seoSet()->localizations()
                    ->mapWithKeys(fn ($localization) => [$localization->locale() => $localization->origin()?->locale()])
                    ->filter()
                    ->put($currentLocale, $value);

                $visited = collect([$currentLocale]);
                $current = $value;

                while ($current !== null) {
                    if ($visited->contains($current)) {
                        $fail('This origin creates a circular dependency. Please choose a different site or clear the origin.');
                        return;
                    }

                    $visited->push($current);
                    $current = $originMap->get($current);
                }
            }
        };
    }
}
