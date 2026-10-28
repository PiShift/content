<?php

namespace PiShift\Content\Fields;

use PiShift\Content\Support\Html;
use PiShift\Content\Support\Localizer;

class RichText extends Field
{
    public function type(): string
    {
        return 'rich-text';
    }

    protected function valueRules(): array
    {
        return ['string', 'max:100000'];
    }

    protected function resolveValue(mixed $value, Localizer $localizer): mixed
    {
        return is_string($value) ? Html::clean($value) : null;
    }
}
