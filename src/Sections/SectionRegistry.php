<?php

namespace PiShift\Content\Sections;

use PiShift\Content\Support\TypeRegistry;

/**
 * @extends TypeRegistry<SectionType>
 */
class SectionRegistry extends TypeRegistry
{
    protected function baseClass(): string
    {
        return SectionType::class;
    }
}
