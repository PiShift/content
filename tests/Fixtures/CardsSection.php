<?php

namespace PiShift\Content\Tests\Fixtures;

use PiShift\Content\Fields\Field;
use PiShift\Content\Sections\SectionType;

class CardsSection extends SectionType
{
    public function fields(): array
    {
        return [
            Field::text('title')->translatable()->required(),
            Field::richText('intro')->translatable(),
            Field::repeater('cards', [
                Field::text('title')->translatable()->required(),
                Field::textarea('text')->translatable(),
                Field::link('link'),
            ])->min(1)->max(3)->itemLabel('title'),
        ];
    }

    public function variants(): array
    {
        return ['grid-3' => '3 columns', 'list' => 'List'];
    }

    public function styles(): array
    {
        return ['background' => ['default' => 'Default', 'muted' => 'Muted']];
    }
}
