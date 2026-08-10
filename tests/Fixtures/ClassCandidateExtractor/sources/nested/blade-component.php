<?php

class BladeFixtureComponent
{
    public string $alignment = 'items-center';

    public function __construct(
        public ?string $width = 'w-72',
        public ?string $maxHeight = 'max-h-96'
    ) {
        //
    }

    public function render(): string
    {
        return <<<'BLADE'
            {{-- @maryClass('blade-comment') --}}
            <!-- @maryClass('html-comment') -->

            <div
                @maryClass('directive-class shared')
                {{ $attributes -> maryClass([
                    'attribute-class',
                    'attribute-conditional' => $selected,
                    $alignment => true,
                    $width => true,
                    $maxHeight => $scroll,
                ]) }}
            >
                <span class="{{ Mary::classes('heredoc-class') }}"></span>
            </div>
            BLADE;
    }
}
