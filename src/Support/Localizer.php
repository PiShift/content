<?php

namespace PiShift\Content\Support;

/**
 * Picks the value of a translatable field for a locale, following the configured fallbacks.
 */
class Localizer
{
    /**
     * @param  list<string>  $locales
     * @param  array<string, list<string>>  $fallbacks
     */
    public function __construct(
        protected array $locales,
        protected array $fallbacks,
        protected ?string $locale = null,
    ) {}

    public static function fromConfig(?string $locale = null): self
    {
        return new self(config('content.locales', ['en']), config('content.fallbacks', []), $locale);
    }

    public function locale(): string
    {
        return $this->locale ?? app()->getLocale();
    }

    public function forLocale(string $locale): self
    {
        return new self($this->locales, $this->fallbacks, $locale);
    }

    /**
     * @return list<string>
     */
    public function locales(): array
    {
        return $this->locales;
    }

    public function defaultLocale(): string
    {
        return $this->locales[0];
    }

    /**
     * A translatable value is an array keyed by locale; plain values are returned as they are.
     */
    public function pick(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        foreach ($this->chain() as $locale) {
            if (filled($value[$locale] ?? null)) {
                return $value[$locale];
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    public function chain(): array
    {
        $locale = $this->locale();

        return array_values(array_unique([$locale, ...($this->fallbacks[$locale] ?? []), $this->defaultLocale()]));
    }
}
