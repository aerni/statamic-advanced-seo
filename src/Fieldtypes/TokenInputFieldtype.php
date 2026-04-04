<?php

namespace Aerni\AdvancedSeo\Fieldtypes;

use Aerni\AdvancedSeo\Context\Context;
use Aerni\AdvancedSeo\Facades\Token;
use Aerni\AdvancedSeo\Features\Ai;
use Aerni\AdvancedSeo\Tokens\Token as TokenContract;
use Statamic\Fields\Fieldtype;

class TokenInputFieldtype extends Fieldtype
{
    protected $selectable = false;

    public function augment($value): ?string
    {
        return Token::parse($value, $this->field);
    }

    public function preload(): array
    {
        return [
            'actions' => $this->actions(),
            'tokens' => Token::for($this->field->parent())
                ->all()
                ->reject(fn (TokenContract $token) => $token->handle() === $this->field->handle())
                ->values()
                ->toArray(),
        ];
    }

    protected function actions(): array
    {
        $context = Context::from($this->field->parent());

        if (! Ai::enabled($context)) {
            return [];
        }

        return [
            ['id' => 'ai-generate'],
        ];
    }
}
