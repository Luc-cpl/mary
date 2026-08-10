<?php

class DynamicFixtureComponent
{
    public string $alignment = 'justify-center';

    public string $tooltipPosition = 'lg:tooltip-top';

    public function mount(bool $left = false, bool $right = false, bool $bottom = false): void
    {
        $this->tooltipPosition = $left
            ? 'lg:tooltip-left'
            : ($right ? 'lg:tooltip-right' : ($bottom ? 'lg:tooltip-bottom' : 'lg:tooltip-top'));
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

    public function stateClass(): string
    {
        $stateClass = 'state-active';

        return $stateClass;
    }

    public function catalog(): void
    {
        Mary::classes($this->alignment);
        Mary::classes($this->stateClass());
    }

    public function render(): string
    {
        return <<<'BLADE'
            <div @maryClass([
                'lg:tooltip $tooltipPosition' => $tooltip,
                'lg:tooltip {$this->tooltipPosition($element)}' => $tooltip,
                'dark:hover:text-white' => check('condition-value-is-not-a-class'),
            ])></div>
            BLADE;
    }
}
