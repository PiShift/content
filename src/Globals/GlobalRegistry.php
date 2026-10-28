<?php

namespace PiShift\Content\Globals;

use PiShift\Content\Support\TypeRegistry;

/**
 * @extends TypeRegistry<GlobalType>
 */
class GlobalRegistry extends TypeRegistry
{
    protected function baseClass(): string
    {
        return GlobalType::class;
    }
}
