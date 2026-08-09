<?php

namespace Mary;

use Illuminate\Support\Arr;
use Stringable;

class ClassBuilder implements Stringable
{
    /** @var array<int, string> */
    protected array $pending = [];

    public function __construct(protected ?string $prefix = null)
    {
        if ($prefix === null || trim($prefix) === '') {
            $this->prefix = null;

            return;
        }

        $this->prefix = str_ends_with($prefix, ':') || str_ends_with($prefix, '-')
            ? $prefix
            : $prefix . ':';
    }

    public function add(string|array|null $classes): static
    {
        $clone = clone $this;
        $clone->pending[] = $this->prefix(Arr::toCssClasses($classes ?? ''));

        return $clone;
    }

    public function addRaw(string|array|null $classes): static
    {
        $clone = clone $this;
        $clone->pending[] = Arr::toCssClasses($classes ?? '');

        return $clone;
    }

    public function __toString(): string
    {
        return implode(' ', array_filter($this->pending));
    }

    protected function prefix(string $classes): string
    {
        if ($classes === '' || $this->prefix === null) {
            return $classes;
        }

        $prefix = $this->prefix;

        return implode(' ', array_map(
            fn (string $class): string => str_starts_with($class, $prefix) ? $class : $prefix . $class,
            preg_split('/\s+/', trim($classes)) ?: []
        ));
    }
}
