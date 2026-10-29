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

    /**
     * One of the options. Not "string": a value read back from an integer column (e.g. a parent
     * id) is sent back as a number, and the in rule already compares as text.
     */
    protected function valueRules(): array
    {
        return [Rule::in(array_map('strval', array_keys($this->options)))];
    }

    protected function extraSchema(): array
    {
        return ['options' => $this->options];
    }
}
