<?php

namespace Aerni\AdvancedSeo\Tokens;

use Statamic\Fields\Field;

class FieldToken implements Token
{
    public function __construct(protected readonly Field $field) {}

    public function handle(): string
    {
        return $this->field->handle();
    }

    public function display(): string
    {
        return $this->field->display();
    }

    public function group(): string
    {
        return __('advanced-seo::messages.token_group_fields');
    }

    public function toArray(): array
    {
        return [
            'handle' => $this->handle(),
            'display' => $this->display(),
            'group' => $this->group(),
        ];
    }
}
