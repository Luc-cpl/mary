<?php

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\MessageBag;
use Illuminate\Support\Str;
use Illuminate\Support\ViewErrorBag;
use Livewire\Livewire;
use Mary\Tests\Support\ComponentHarness;

function componentClassContexts(string $html): array
{
    preg_match_all('/(?:^|\s)([^\s=]*class[^\s=]*)=(["\'])(.*?)\2/si', $html, $attributes, PREG_SET_ORDER);
    preg_match_all('/classList\.(add|remove)\(\s*(["\'])(.*?)\2/si', $html, $classListCalls, PREG_SET_ORDER);

    $contexts = [
        ...array_map(
            fn (array $match): string => $match[1].'='.preg_replace('/\s+/', ' ', trim(html_entity_decode($match[3], ENT_QUOTES | ENT_HTML5))),
            $attributes
        ),
        ...array_map(
            fn (array $match): string => 'classList.'.$match[1].'='.preg_replace('/\s+/', ' ', trim(html_entity_decode($match[3], ENT_QUOTES | ENT_HTML5))),
            $classListCalls
        ),
    ];

    sort($contexts);

    return $contexts;
}

function componentContextsContainToken(array $contexts, string $candidate): bool
{
    $pattern = '/(?:^|[\s"\'=])'.preg_quote($candidate, '/').'(?=$|[\s"\'<>;,)])/m';

    return preg_match($pattern, implode("\n", $contexts)) === 1;
}

it('has a render fixture for every component except the separately tested calendar', function () {
    $aliases = [
        'Colorpicker' => 'colorpicker',
        'DatePicker' => 'datepicker',
        'DateTime' => 'datetime',
    ];
    $components = [];

    foreach (glob(dirname(__DIR__, 2).'/src/View/Components/*.php') as $path) {
        $class = pathinfo($path, PATHINFO_FILENAME);

        if ($class !== 'Calendar') {
            $components[] = $aliases[$class] ?? Str::kebab($class);
        }
    }

    sort($components);
    $fixtures = array_keys(require dirname(__DIR__).'/Fixtures/ComponentRenderCases.php');
    sort($fixtures);

    expect($fixtures)->toBe($components);
});

it('matches the independently stored class-context fixture for every component', function () {
    $contracts = [];

    foreach (require dirname(__DIR__).'/Fixtures/ComponentRenderCases.php' as $component => $template) {
        foreach (['unprefixed' => null, 'prefixed' => 'tw'] as $mode => $prefix) {
            config()->set('mary.tailwind_prefix', $prefix);
            $html = Livewire::test(ComponentHarness::class, ['template' => $template])->html();
            $contexts = componentClassContexts($html);

            expect($html)->not->toBe('')
                ->and($contexts)->not->toBeEmpty("[{$component}] No rendered class context was captured.");

            $contracts[$component][$mode] = $contexts;
        }
    }

    $fixture = json_decode(
        file_get_contents(dirname(__DIR__).'/Fixtures/ComponentClassContexts.json'),
        true,
        512,
        JSON_THROW_ON_ERROR
    );

    expect($contracts)->toBe($fixture);
});

it('prefixes conditional states while preserving fixture-defined consumer classes', function (array $case) {
    $errors = new ViewErrorBag;
    $errors->put('default', new MessageBag($case['errors'] ?? []));

    view()->share('errors', $errors);

    foreach (['unprefixed' => null, 'prefixed' => 'tw'] as $mode => $prefix) {
        config()->set('mary.tailwind_prefix', $prefix);
        $html = ($case['livewire'] ?? false)
            ? Livewire::test(ComponentHarness::class, ['template' => $case['template']])->html()
            : Blade::render($case['template']);
        $contexts = componentClassContexts($html);

        foreach ($case['internal'] as $candidate) {
            $expected = $prefix === null ? $candidate : 'tw:'.$candidate;

            expect(componentContextsContainToken($contexts, $expected))
                ->toBeTrue("[{$mode}] Missing internal conditional class [{$expected}].");

            if ($prefix !== null) {
                expect(componentContextsContainToken($contexts, $candidate))
                    ->toBeFalse("[{$mode}] Internal conditional class remained unprefixed [{$candidate}].");
            }
        }

        foreach ($case['raw'] ?? [] as $candidate) {
            expect(componentContextsContainToken($contexts, $candidate))
                ->toBeTrue("[{$mode}] Missing consumer class [{$candidate}].")
                ->and(componentContextsContainToken($contexts, 'tw:'.$candidate))
                ->toBeFalse("[{$mode}] Consumer class was prefixed [tw:{$candidate}].");
        }

        foreach ($case['absent'] ?? [] as $candidate) {
            expect(componentContextsContainToken($contexts, $candidate))
                ->toBeFalse("[{$mode}] Suppressed default class was rendered [{$candidate}].")
                ->and(componentContextsContainToken($contexts, 'tw:'.$candidate))
                ->toBeFalse("[{$mode}] Suppressed default class was rendered [tw:{$candidate}].");
        }
    }
})->with(array_map(
    fn (array $case): array => [$case],
    require dirname(__DIR__).'/Fixtures/ComponentStateCases.php'
));

it('prefixes calendar classes embedded in setup JSON and popup markup', function () {
    $events = [[
        'date' => '2026-08-09',
        'label' => 'Event',
        'description' => 'Description',
        'css' => 'consumer-event',
    ]];

    config()->set('mary.tailwind_prefix', null);
    $unprefixed = html_entity_decode(
        Blade::render('<x-calendar :events="$events" />', ['events' => $events]),
        ENT_QUOTES | ENT_HTML5
    );

    config()->set('mary.tailwind_prefix', 'tw');
    $prefixed = html_entity_decode(
        Blade::render('<x-calendar :events="$events" />', ['events' => $events]),
        ENT_QUOTES | ENT_HTML5
    );

    expect($unprefixed)->toContain('vc w-fit', 'vc-grid justify-center', 'vc-column !min-w-fit !max-w-fit')
        ->toContain('my-3 last:hidden')
        ->and($prefixed)->toContain('vc tw:w-fit', 'vc-grid tw:justify-center')
        ->toContain('vc-column tw:!min-w-fit tw:!max-w-fit')
        ->toContain('tw:my-3 tw:last:hidden')
        ->not->toContain('vc w-fit')
        ->not->toContain('my-3 last:hidden');
});

it('does not transform classes supplied by the application while merging attributes', function () {
    config()->set('mary.tailwind_prefix', 'tw');

    $html = Blade::render('<x-button class="consumer-class hover:consumer-state">Save</x-button>');
    $swap = Blade::render('<x-swap icon-size="consumer-icon-size">Swap</x-swap>');

    expect($html)->toContain('tw:btn')
        ->toContain('consumer-class hover:consumer-state')
        ->not->toContain('tw:consumer-class')
        ->not->toContain('tw:hover:consumer-state')
        ->and($swap)->toContain('tw:swap')
        ->toContain('consumer-icon-size')
        ->not->toContain('tw:consumer-icon-size');
});

it('prefixes Alpine Livewire slot and nested component defaults', function () {
    config()->set('mary.tailwind_prefix', 'tw');

    $theme = Blade::render('<x-theme-toggle class="consumer-toggle" />');
    $card = Blade::render(<<<'BLADE'
        <x-card title="Account" class="consumer-card">
            <x-slot:actions class="consumer-actions"><x-button>Save</x-button></x-slot:actions>
            Content
        </x-card>
        BLADE);
    $button = Blade::render('<x-button icon="o-home" spinner="save" wire:click="save">Save</x-button>');

    expect($theme)->toContain("classList.add('tw:swap-off')")
        ->toContain('consumer-toggle')
        ->not->toContain('theme-controller')
        ->and($card)->toContain('tw:card')
        ->toContain('tw:flex')
        ->toContain('consumer-card')
        ->toContain('consumer-actions')
        ->and($button)->toContain('wire:loading')
        ->toContain('tw:hidden');
});

it('keeps dynamically composed tooltip positions unchanged', function (?string $prefix, string $expectedPrefix) {
    config()->set('mary.tailwind_prefix', $prefix);

    $button = Blade::render('<x-button tooltip-left="Help">Button</x-button>');
    $stat = Blade::render('<x-stat tooltip-right="Help" />');
    $breadcrumbs = Blade::render('<x-breadcrumbs :items="$items" />', [
        'items' => [['label' => 'First', 'tooltip-bottom' => 'Help']],
    ]);

    expect($button)->toContain("{$expectedPrefix}!inline-flex {$expectedPrefix}lg:tooltip {$expectedPrefix}lg:tooltip-left")
        ->and($stat)->toContain("{$expectedPrefix}lg:tooltip {$expectedPrefix}lg:tooltip-right")
        ->and($breadcrumbs)->toContain("{$expectedPrefix}lg:tooltip {$expectedPrefix}lg:tooltip-bottom");
})->with([
    'without prefix' => [null, ''],
    'with prefix' => ['tw', 'tw:'],
]);
