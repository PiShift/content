<?php

namespace PiShift\Content\Collections;

use PiShift\Content\Support\TypeRegistry;

/**
 * @extends TypeRegistry<CollectionType>
 */
class CollectionRegistry extends TypeRegistry
{
    protected function baseClass(): string
    {
        return CollectionType::class;
    }
}
