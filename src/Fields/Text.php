<?php

namespace PiShift\Content\Fields;

class Text extends Field
{
    protected int $maxLength = 255;

    protected ?string $placeholder = null;

    /** @var 'text'|'email'|'password' */
    protected string $inputType = 'text';

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

    public function email(): static
    {
        $this->inputType = 'email';

        return $this;
    }

    /**
     * A password input; the form never receives the stored value back.
     */
    public function password(): static
    {
        $this->inputType = 'password';

        return $this;
    }

    public function isPassword(): bool
    {
        return $this->inputType === 'password';
    }

    public function type(): string
    {
        return 'text';
    }

    protected function valueRules(): array
    {
        return ['string', "max:{$this->maxLength}", ...($this->inputType === 'email' ? ['email'] : [])];
    }

    protected function extraSchema(): array
    {
        return ['maxLength' => $this->maxLength, 'placeholder' => $this->placeholder, 'inputType' => $this->inputType];
    }
}
