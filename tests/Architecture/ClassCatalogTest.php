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
        ->toHaveCount(518)
        ->and(hash('sha256', implode("\n", $classes)))
        ->toBe('47ab311198dd906f4d5f2794fac05fb9c62211681efd4ea3bd121e6ef72004fe')
        ->and($classes)
        ->toContain('btn', 'hover:bg-base-200', '[&_.mary-hideable]:hidden', 'max-h-64', 'max-h-96')
        ->toContain('lg:tooltip-left', 'lg:tooltip-right', 'lg:tooltip-bottom', 'lg:tooltip-top')
        ->not->toContain('right', 'right-mobile', 'true');
});
