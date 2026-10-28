<?php

namespace PiShift\Content\Fields;

use Illuminate\Support\Carbon;
use PiShift\Content\Support\Localizer;

class Date extends Field
{
    public function type(): string
    {
        return 'date';
    }

    protected function valueRules(): array
    {
        return ['date'];
    }

    protected function resolveValue(mixed $value, Localizer $localizer): mixed
    {
        return filled($value) ? Carbon::parse($value) : null;
    }
}
