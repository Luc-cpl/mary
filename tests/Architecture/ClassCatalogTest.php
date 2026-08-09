<?php

use Mary\Support\ClassCandidateExtractor;

it('extracts internal class candidates directly from the package source', function () {
    $classes = ClassCandidateExtractor::fromPaths([
        dirname(__DIR__, 2).'/src/View/Components',
        dirname(__DIR__, 2).'/src/Traits/Toast.php',
    ]);
    $sorted = $classes;
    sort($sorted);

    expect($classes)->toBe($sorted)
        ->toBe(array_values(array_unique($classes)))
        ->toHaveCount(513)
        ->and(hash('sha256', implode("\n", $classes)))
        ->toBe('ad97d4826be244f5e874077ab9891be12cb813d2ee9a452b29eece26c17655fe')
        ->and($classes)
        ->toContain('btn', 'hover:bg-base-200', '[&_.mary-hideable]:hidden')
        ->toContain('lg:tooltip-left', 'lg:tooltip-right', 'lg:tooltip-bottom', 'lg:tooltip-top')
        ->not->toContain('right', 'right-mobile', 'true');
});
