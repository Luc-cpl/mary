<?php

use Livewire\Component;

new class extends Component
{
    public string $tooltipPosition = 'lg:tooltip-top';

    public function mount(bool $left = false, bool $right = false, bool $bottom = false): void
    {
        $this->tooltipPosition = $left ? 'lg:tooltip-left' : ($right ? 'lg:tooltip-right' : ($bottom ? 'lg:tooltip-bottom' : 'lg:tooltip-top'));
    }

    public function tooltipPosition(array $element): string
    {
        return match (true) {
            isset($element['tooltip-left']) => 'lg:tooltip-left',
            isset($element['tooltip-right']) => 'lg:tooltip-right',
            isset($element['tooltip-bottom']) => 'lg:tooltip-bottom',
            default => 'lg:tooltip-top',
        };
    }
};
?>

<div
    @maryClass([
        '!block -mt-4',
        '[&_.item]:block' => $visible,
        'dark:hover:text-white' => check('condition-value-is-not-a-class'),
        "lg:tooltip $tooltipPosition" => $tooltip,
        "lg:tooltip {$this->tooltipPosition($element)}" => $tooltip,
    ])
>
    <span {{ $attributes->maryClass(['text-sm']) }}></span>
    <span class="{{ Mary::classes('w-[calc(100%-1rem)]') }}"></span>
    <span class="{{ Mary::classes()->addRaw('application-class') }}"></span>
</div>
