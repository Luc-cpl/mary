<?php

use Mary\Support\ClassCandidateExtractor;

it('extracts the exact catalog from independent source fixtures', function () {
    $fixtures = dirname(__DIR__).'/Fixtures/ClassCandidateExtractor';

    expect(ClassCandidateExtractor::fromPaths([$fixtures.'/sources']))
        ->toBe(require $fixtures.'/expected.php');
});

it('fails when a dynamic candidate cannot be resolved', function () {
    $path = dirname(__DIR__).'/Fixtures/ClassCandidateExtractor/errors/unresolved.php';

    expect(fn () => ClassCandidateExtractor::fromPaths([$path]))
        ->toThrow(RuntimeException::class, 'Unable to resolve dynamic Mary class [{$missingClass}]');
});

it('fails fast when dynamic candidates reference each other cyclically', function () {
    $path = dirname(__DIR__).'/Fixtures/ClassCandidateExtractor/errors/cyclic.php';

    expect(fn () => ClassCandidateExtractor::fromPaths([$path]))
        ->toThrow(RuntimeException::class, 'Cyclic dynamic Mary class reference [$firstClass]');
});

it('fails when a configured source path does not exist', function () {
    $path = dirname(__DIR__).'/Fixtures/ClassCandidateExtractor/missing';

    expect(fn () => ClassCandidateExtractor::fromPaths([$path]))
        ->toThrow(RuntimeException::class, "Mary class source path [{$path}] does not exist.");
});
