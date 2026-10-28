<?php

namespace PiShift\Content\Fields;

class DateTime extends Date
{
    public function type(): string
    {
        return 'datetime';
    }
}
