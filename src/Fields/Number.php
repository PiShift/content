<?php

namespace PiShift\Content\Fields;

class Number extends Field
{
    protected ?float $min = null;

    protected ?float $max = null;

    protected bool $integer = true;

    public function min(float $min): static
    {
        $this->min = $min;

        return $this;
    }

    public function max(float $max): static
    {
        $this->max = $max;

        return $this;
    }

    public function decimal(): static
    {
        $this->integer = false;

        return $this;
    }

    public function type(): string
    {
        return 'number';
    }

    protected function valueRules(): array
    {
        return array_values(array_filter([
            $this->integer ? 'integer' : 'numeric',
            $this->min !== null ? "min:{$this->min}" : null,
            $this->max !== null ? "max:{$this->max}" : null,
        ]));
    }

    protected function extraSchema(): array
    {
        return ['min' => $this->min, 'max' => $this->max, 'integer' => $this->integer];
    }
}
