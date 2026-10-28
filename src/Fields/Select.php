<?php

namespace PiShift\Content\Fields;

use Illuminate\Validation\Rule;

class Select extends Field
{
    /** @var array<string, string> */
    protected array $options = [];

    /**
     * @param  array<string, string>  $options  value => label
     */
    public function options(array $options): static
    {
        $this->options = $options;

        return $this;
    }

    public function type(): string
    {
        return 'select';
    }

    protected function valueRules(): array
    {
        return ['string', Rule::in(array_map('strval', array_keys($this->options)))];
    }

    protected function extraSchema(): array
    {
        return ['options' => $this->options];
    }
}
