<?php

namespace PiShift\Content\Fields;

/**
 * A colour as a 6-digit hex value ("#1a6b3a"), e.g. for theme tokens.
 */
class Color extends Field
{
    public function type(): string
    {
        return 'color';
    }

    protected function valueRules(): array
    {
        return ['string', 'regex:/^#[0-9a-fA-F]{6}$/'];
    }

    public function normalize(mixed $value): mixed
    {
        return is_string($value) && $value !== '' ? strtolower($value) : $this->default;
    }
}
