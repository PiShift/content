<?php

namespace PiShift\Content\Collections;

use Closure;

/**
 * A button on a record's admin page that runs code on it: change a status, re-run a job…
 */
class Action
{
    protected ?string $label = null;

    protected string $icon = 'check';

    /** @var 'primary'|'neutral'|'danger' */
    protected string $tone = 'neutral';

    protected ?string $confirm = null;

    protected ?Closure $visible = null;

    protected ?Closure $handle = null;

    final public function __construct(protected string $key) {}

    public static function make(string $key): static
    {
        return new static($key);
    }

    public function label(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function icon(string $icon): static
    {
        $this->icon = $icon;

        return $this;
    }

    /**
     * @param  'primary'|'neutral'|'danger'  $tone
     */
    public function tone(string $tone): static
    {
        $this->tone = $tone;

        return $this;
    }

    /**
     * Ask for confirmation with this message before running.
     */
    public function confirm(string $message): static
    {
        $this->confirm = $message;

        return $this;
    }

    /**
     * fn (object $record): bool — hide the action for some records.
     */
    public function visible(Closure $callback): static
    {
        $this->visible = $callback;

        return $this;
    }

    /**
     * fn (object $record): ?string — runs the action; the returned text is shown as a toast.
     */
    public function handle(Closure $callback): static
    {
        $this->handle = $callback;

        return $this;
    }

    public function key(): string
    {
        return $this->key;
    }

    public function isVisibleFor(object $record): bool
    {
        return $this->visible === null || (bool) ($this->visible)($record);
    }

    public function run(object $record): ?string
    {
        return $this->handle ? ($this->handle)($record) : null;
    }

    /**
     * @return array{key: string, label: string, icon: string, tone: string, confirm: ?string}
     */
    public function toSchema(): array
    {
        return [
            'key' => $this->key,
            'label' => $this->label ?? $this->key,
            'icon' => $this->icon,
            'tone' => $this->tone,
            'confirm' => $this->confirm,
        ];
    }
}
