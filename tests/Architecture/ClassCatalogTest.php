<?php

use Mary\Support\ClassCandidateExtractor;

it('extracts internal class candidates directly from the package source', function () {
    $classes = ClassCandidateExtractor::fromPaths([
        dirname(__DIR__, 2).'/src/View/Components',
        dirname(__DIR__, 2).'/src/Traits/Toast.php',
    ]);

    expect($classes)->toBe(array_values(array_unique($classes)))
        ->toContain('btn', 'hover:bg-base-200', '[&_.mary-hideable]:hidden')
        ->toContain('lg:tooltip-left', 'lg:tooltip-right', 'lg:tooltip-bottom', 'lg:tooltip-top')
        ->not->toContain('right', 'right-mobile', 'true');
});
