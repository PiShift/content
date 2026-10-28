<?php

namespace PiShift\Content\Fields;

use PiShift\Content\Support\Localizer;

class Toggle extends Field
{
    protected mixed $default = false;

    public function type(): string
    {
        return 'toggle';
    }

    protected function valueRules(): array
    {
        return ['boolean'];
    }

    protected function resolveValue(mixed $value, Localizer $localizer): mixed
    {
        return (bool) $value;
    }
}
