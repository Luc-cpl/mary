<?php

namespace Mary\Support;

class ClassSourceRegistry
{
    /** @var array<int, string> */
    protected array $paths = [];

    /** @param  array<int, string>  $paths */
    public function __construct(array $paths = [])
    {
        $this->add($paths);
    }

    /** @param  string|array<int, string>  $paths */
    public function add(string|array $paths): static
    {
        foreach ((array) $paths as $path) {
            if ($path !== '' && ! in_array($path, $this->paths, true)) {
                $this->paths[] = $path;
            }
        }

        return $this;
    }

    /** @return array<int, string> */
    public function all(): array
    {
        return $this->paths;
    }
}
