<?php

namespace PiShift\Content\Fields;

class Textarea extends Text
{
    protected int $maxLength = 5000;

    protected int $rows = 4;

    public function rows(int $rows): static
    {
        $this->rows = $rows;

        return $this;
    }

    public function type(): string
    {
        return 'textarea';
    }

    protected function extraSchema(): array
    {
        return [...parent::extraSchema(), 'rows' => $this->rows];
    }
}
