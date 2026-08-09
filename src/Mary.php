<?php

namespace Mary;

class Mary
{
    public function classes(string|array|null $classes = null): ClassBuilder
    {
        $builder = new ClassBuilder(config('mary.tailwind_prefix'));

        return $classes === null ? $builder : $builder->add($classes);
    }
}
