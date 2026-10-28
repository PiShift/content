<?php

namespace PiShift\Content\Fields;

class Text extends Field
{
    protected int $maxLength = 255;

    protected ?string $placeholder = null;

    public function maxLength(int $maxLength): static
    {
        $this->maxLength = $maxLength;

        return $this;
    }

    public function placeholder(string $placeholder): static
    {
        $this->placeholder = $placeholder;

        return $this;
    }

    public function type(): string
    {
        return 'text';
    }

    protected function valueRules(): array
    {
        return ['string', "max:{$this->maxLength}"];
    }

    protected function extraSchema(): array
    {
        return ['maxLength' => $this->maxLength, 'placeholder' => $this->placeholder];
    }
}
