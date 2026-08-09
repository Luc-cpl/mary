<?php

namespace Mary;

use Mary\Support\ClassSourceRegistry;

class Mary
{
    public function classes(string|array|null $classes = null): ClassBuilder
    {
        $builder = new ClassBuilder(config('mary.tailwind_prefix'));

        return $classes === null ? $builder : $builder->add($classes);
    }

    /** @param  string|array<int, string>  $paths */
    public function addClassSourcePath(string|array $paths): static
    {
        app(ClassSourceRegistry::class)->add($paths);

        return $this;
    }
}
