<?php

use Illuminate\Support\Facades\Blade;
use Livewire\Livewire;
use Mary\Support\ClassCandidateExtractor;
use Mary\Tests\Support\ComponentHarness;

it('prefixes every rendered internal candidate while preserving the unprefixed output mode', function (string $component, string $template) {
    static $candidates;

    $candidates ??= ClassCandidateExtractor::fromPaths([
        dirname(__DIR__, 2) . '/src/View/Components',
        dirname(__DIR__, 2) . '/src/Traits/Toast.php',
    ]);

    $classContexts = static function (string $html): string {
        $html = html_entity_decode($html, ENT_QUOTES | ENT_HTML5);
        preg_match_all('/(?:^|\s)[^\s=]*class[^\s=]*=(["\'])(.*?)\1/si', $html, $attributes);
        preg_match_all('/classList\.(?:add|remove)\(\s*(["\'])(.*?)\1/si', $html, $classListCalls);

        return implode("\n", [...$attributes[2], ...$classListCalls[2]]);
    };

    $containsToken = static function (string $contexts, string $candidate): bool {
        $pattern = '/(?:^|[\s"\'=])'.preg_quote($candidate, '/') . '(?=$|[\s"\'<>;,)])/m';

        return preg_match($pattern, $contexts) === 1;
    };

    config()->set('mary.tailwind_prefix', null);
    $unprefixed = Livewire::test(ComponentHarness::class, ['template' => $template])->html();

    config()->set('mary.tailwind_prefix', 'tw');
    $prefixed = Livewire::test(ComponentHarness::class, ['template' => $template])->html();
    $unprefixedContexts = $classContexts($unprefixed);
    $prefixedContexts = $classContexts($prefixed);

    $renderedCandidates = array_values(array_filter(
        $candidates,
        fn (string $candidate): bool => $containsToken($unprefixedContexts, $candidate)
    ));

    expect($unprefixed)->not->toBe('')
        ->and($renderedCandidates)->not->toBeEmpty();

    foreach ($renderedCandidates as $candidate) {
        expect($containsToken($prefixedContexts, 'tw:'.$candidate))
            ->toBeTrue("[{$component}] Missing prefixed candidate [tw:{$candidate}].")
            ->and($containsToken($prefixedContexts, $candidate))
            ->toBeFalse("[{$component}] Internal candidate remained unprefixed [{$candidate}].");
    }

    expect($component)->not->toBe('');
})->with('components');

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

dataset('components', function () {
    return [
        'accordion' => ['accordion', '<x-accordion />'],
        'alert' => ['alert', '<x-alert title="Alert" />'],
        'avatar' => ['avatar', '<x-avatar />'],
        'badge' => ['badge', '<x-badge value="1" />'],
        'breadcrumbs' => ['breadcrumbs', '<x-breadcrumbs :items="[]" />'],
        'button' => ['button', '<x-button>Button</x-button>'],
        'card' => ['card', '<x-card>Card</x-card>'],
        'carousel' => ['carousel', '<x-carousel :slides="[]" />'],
        'chart' => ['chart', '<x-chart wire:model="chart" />'],
        'checkbox' => ['checkbox', '<x-checkbox />'],
        'choices' => ['choices', '<x-choices wire:model="choice" />'],
        'choices-offline' => ['choices-offline', '<x-choices-offline wire:model="choice" :options="[]" />'],
        'code' => ['code', '<x-code value="code" />'],
        'collapse' => ['collapse', '<x-collapse :heading="$this->slot(\'Heading\')" :content="$this->slot(\'Content\')" />'],
        'colorpicker' => ['colorpicker', '<x-colorpicker wire:model="color" />'],
        'datepicker' => ['datepicker', '<x-datepicker wire:model="date" />'],
        'datetime' => ['datetime', '<x-datetime wire:model="date" />'],
        'diff' => ['diff', '<x-diff />'],
        'drawer' => ['drawer', '<x-drawer wire:model="drawer">Drawer</x-drawer>'],
        'dropdown' => ['dropdown', '<x-dropdown>Menu</x-dropdown>'],
        'editor' => ['editor', '<x-editor wire:model="text" />'],
        'errors' => ['errors', '<x-errors />'],
        'file' => ['file', '<x-file wire:model="file" />'],
        'form' => ['form', '<x-form>Form</x-form>'],
        'group' => ['group', '<x-group>Group</x-group>'],
        'header' => ['header', '<x-header title="Title" />'],
        'hr' => ['hr', '<x-hr />'],
        'icon' => ['icon', '<x-icon name="o-home" />'],
        'image-gallery' => ['image-gallery', '<x-image-gallery :images="[]" />'],
        'image-library' => ['image-library', '<x-image-library wire:model="images" />'],
        'input' => ['input', '<x-input wire:model="name" />'],
        'kbd' => ['kbd', '<x-kbd>Ctrl</x-kbd>'],
        'list-item' => ['list-item', '<x-list-item :item="[\'name\' => \'Mary\']" />'],
        'loading' => ['loading', '<x-loading />'],
        'main' => ['main', '<x-main><x-slot:content>Main</x-slot:content></x-main>'],
        'markdown' => ['markdown', '<x-markdown wire:model="text" />'],
        'menu' => ['menu', '<x-menu>Menu</x-menu>'],
        'menu-item' => ['menu-item', '<x-menu-item title="Item" />'],
        'menu-separator' => ['menu-separator', '<x-menu-separator />'],
        'menu-sub' => ['menu-sub', '<x-menu-sub title="Sub"><x-menu-item title="Item" /></x-menu-sub>'],
        'menu-title' => ['menu-title', '<x-menu-title title="Title" />'],
        'modal' => ['modal', '<x-modal wire:model="modal">Modal</x-modal>'],
        'nav' => ['nav', '<x-nav />'],
        'pagination' => ['pagination', '<x-pagination :rows="$this->paginator()" />'],
        'password' => ['password', '<x-password wire:model="password" />'],
        'pin' => ['pin', '<x-pin :size="4" wire:model="pin" />'],
        'popover' => ['popover', '<x-popover><x-slot:trigger>Trigger</x-slot:trigger><x-slot:content>Content</x-slot:content></x-popover>'],
        'progress' => ['progress', '<x-progress />'],
        'progress-radial' => ['progress-radial', '<x-progress-radial value="50" />'],
        'radio' => ['radio', '<x-radio wire:model="radio" :options="[]" />'],
        'range' => ['range', '<x-range wire:model="range" />'],
        'rating' => ['rating', '<x-rating wire:model="rating" />'],
        'select' => ['select', '<x-select wire:model="select" :options="[]" />'],
        'select-group' => ['select-group', '<x-select-group wire:model="select" :options="[]" />'],
        'signature' => ['signature', '<x-signature wire:model="signature" />'],
        'spotlight' => ['spotlight', '<x-spotlight />'],
        'stat' => ['stat', '<x-stat title="Stat" />'],
        'step' => ['step', '<x-step :step="1" text="One" />'],
        'steps' => ['steps', '<x-steps>Steps</x-steps>'],
        'swap' => ['swap', '<x-swap>Swap</x-swap>'],
        'tab' => ['tab', '<x-tabs><x-tab name="one" label="One">Tab</x-tab></x-tabs>'],
        'table' => ['table', '<x-table :headers="[]" :rows="[]" />'],
        'tabs' => ['tabs', '<x-tabs>Tabs</x-tabs>'],
        'tags' => ['tags', '<x-tags wire:model="tags" />'],
        'textarea' => ['textarea', '<x-textarea wire:model="text" />'],
        'theme-toggle' => ['theme-toggle', '<x-theme-toggle />'],
        'timeline-item' => ['timeline-item', '<x-timeline-item title="Event" />'],
        'toast' => ['toast', '<x-toast />'],
        'toggle' => ['toggle', '<x-toggle wire:model="toggle" />'],
    ];
});
