<?php

namespace PiShift\Content\Support;

use Illuminate\Support\Str;
use InvalidArgumentException;
use ReflectionClass;
use Symfony\Component\Finder\Finder;

/**
 * Holds the section or global types of the application, discovered from a directory or
 * registered by hand.
 *
 * @template T of object
 */
abstract class TypeRegistry
{
    /** @var array<string, T> */
    protected array $types = [];

    /**
     * @return class-string<T>
     */
    abstract protected function baseClass(): string;

    /**
     * @param  class-string<T>|T  $type
     */
    public function register(string|object $type): static
    {
        $instance = is_string($type) ? app($type) : $type;

        if (! $instance instanceof ($this->baseClass())) {
            throw new InvalidArgumentException(get_class($instance).' must extend '.$this->baseClass().'.');
        }

        $this->types[$instance->key()] = $instance;

        return $this;
    }

    /**
     * Register every concrete subclass of the base class found in a directory.
     */
    public function discover(string $path, string $namespace): static
    {
        if (! is_dir($path)) {
            return $this;
        }

        foreach ((new Finder)->files()->in($path)->name('*.php') as $file) {
            $class = $namespace.'\\'.str_replace(['/', '.php'], ['\\', ''], Str::after($file->getPathname(), rtrim($path, '/').'/'));

            if (class_exists($class) && is_subclass_of($class, $this->baseClass()) && ! (new ReflectionClass($class))->isAbstract()) {
                $this->register($class);
            }
        }

        return $this;
    }

    public function has(string $key): bool
    {
        return isset($this->types[$key]);
    }

    /**
     * @return T|null
     */
    public function get(string $key): ?object
    {
        return $this->types[$key] ?? null;
    }

    /**
     * @return array<string, T>
     */
    public function all(): array
    {
        return $this->types;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function toSchema(): array
    {
        return array_values(array_map(fn (object $type): array => $type->toSchema(), $this->types));
    }
}
