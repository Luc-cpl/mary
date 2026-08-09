<?php

use Mary\ClassBuilder;
use Mary\Facades\Mary;

it('keeps classes unchanged when no prefix is configured', function (string|array|null $input, string $expected) {
    expect((string) new ClassBuilder(null)->add($input))->toBe($expected);
})->with([
    'string' => ['btn flex', 'btn flex'],
    'conditional array' => [['btn', 'hidden' => false, 'flex' => true], 'btn flex'],
    'empty' => [null, ''],
]);

it('treats blank prefixes as disabled', function (?string $prefix) {
    expect((string) new ClassBuilder($prefix)->add('btn flex'))->toBe('btn flex');
})->with([
    'null' => null,
    'empty' => '',
    'whitespace' => ' ',
]);

it('prefixes Tailwind and DaisyUI candidates with the expected separator', function (string $prefix, string $expected) {
    expect((string) new ClassBuilder($prefix)->add('btn bg-base-100 flex'))
        ->toBe($expected);
})->with([
    'default separator' => ['tw', 'tw:btn tw:bg-base-100 tw:flex'],
    'colon separator' => ['tw:', 'tw:btn tw:bg-base-100 tw:flex'],
    'dash separator' => ['tw-', 'tw-btn tw-bg-base-100 tw-flex'],
]);

it('supports variants negatives arbitrary values important classes and conditions', function (string $class) {
    expect((string) new ClassBuilder('tw')->add([$class, 'hidden' => false]))
        ->toBe('tw:'.$class);
})->with([
    'variant' => 'hover:bg-base-200',
    'stacked variants' => 'dark:sm:hover:text-white',
    'negative' => '-mt-4',
    'arbitrary' => 'w-[calc(100%-1rem)]',
    'arbitrary variant' => '[&_.mary-hideable]:hidden',
    'leading important' => '!bg-primary',
    'trailing important' => 'size-3!',
]);

it('keeps raw classes untouched and does not prefix twice', function (string $prefix, string $prefixed) {
    $classes = new ClassBuilder($prefix);

    expect((string) $classes
        ->add("{$prefixed}btn flex")
        ->addRaw(['mary-hideable', 'consumer-class' => true, 'ignored' => false]))
        ->toBe("{$prefixed}btn {$prefixed}flex mary-hideable consumer-class");
})->with([
    'default separator' => ['tw', 'tw:'],
    'colon separator' => ['tw:', 'tw:'],
    'dash separator' => ['tw-', 'tw-'],
]);

it('does not retain prefix state in the facade singleton', function () {
    config()->set('mary.tailwind_prefix', 'one');
    $first = (string) Mary::classes('btn');

    config()->set('mary.tailwind_prefix', 'two');
    $second = (string) Mary::classes('btn');

    expect($first)->toBe('one:btn')
        ->and($second)->toBe('two:btn');
});
