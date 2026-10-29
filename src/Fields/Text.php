<?php

namespace PiShift\Content\Fields;

class Text extends Field
{
    protected int $maxLength = 255;

    protected ?string $placeholder = null;

    /** @var 'text'|'email'|'password' */
    protected string $inputType = 'text';

    protected ?string $slugFrom = null;

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

    /**
     * A URL slug filled from a sibling field (e.g. the title) while the record is new; the
     * editor can unlock it and type their own.
     */
    public function slug(string $from = 'title'): static
    {
        $this->slugFrom = $from;

        return $this->rules(['regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/']);
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
        return ['maxLength' => $this->maxLength, 'placeholder' => $this->placeholder, 'inputType' => $this->inputType, 'slugFrom' => $this->slugFrom];
    }
}
