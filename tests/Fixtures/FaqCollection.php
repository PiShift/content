<?php

namespace PiShift\Content\Tests\Fixtures;

use PiShift\Content\Collections\CollectionType;
use PiShift\Content\Collections\Drivers\CollectionDriver;
use PiShift\Content\Collections\Drivers\EntriesDriver;
use PiShift\Content\Fields\Field;

class FaqCollection extends CollectionType
{
    public function fields(): array
    {
        return [
            Field::text('question')->translatable()->required(),
            Field::textarea('answer')->translatable(),
            Field::select('topic')->options(['admission' => 'Admission', 'exams' => 'Exams']),
        ];
    }

    public function driver(): CollectionDriver
    {
        return new EntriesDriver;
    }

    public function filters(): array
    {
        return ['topic' => ['admission' => 'Admission', 'exams' => 'Exams']];
    }

    public function searchable(): array
    {
        return ['question'];
    }
}
