<?php

namespace PiShift\Content\Tests\Fixtures;

use PiShift\Content\Fields\Field;
use PiShift\Content\Sections\SectionType;

/**
 * Version 2 renamed "heading" to "title".
 */
class HeroSection extends SectionType
{
    public function version(): int
    {
        return 2;
    }

    public function fields(): array
    {
        return [
            Field::text('title')->translatable()->required(),
            Field::image('background'),
        ];
    }

    public function upgrade(array $data, int $fromVersion): array
    {
        if ($fromVersion < 2 && array_key_exists('heading', $data)) {
            $data['title'] = $data['heading'];
            unset($data['heading']);
        }

        return $data;
    }
}
