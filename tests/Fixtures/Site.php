<?php

namespace PiShift\Content\Tests\Fixtures;

use PiShift\Content\Fields\Field;
use PiShift\Content\Globals\GlobalType;

class Site extends GlobalType
{
    public function fields(): array
    {
        return [
            Field::text('name')->translatable()->required(),
            Field::text('phone'),
        ];
    }
}
