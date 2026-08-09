<?php

namespace Mary\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Stat extends Component
{
    public string $uuid;

    public string $tooltipPosition = 'lg:tooltip-top';

    public function __construct(
        public ?string $id = null,
        public ?string $value = null,
        public ?string $icon = null,
        public ?string $color = '',
        public ?string $title = null,
        public ?string $description = null,
        public ?string $tooltip = null,
        public ?string $tooltipLeft = null,
        public ?string $tooltipRight = null,
        public ?string $tooltipBottom = null,

    ) {
        $this->uuid = 'mary' . md5(serialize($this)) . $id;
        $this->tooltip = $this->tooltip ?? $this->tooltipLeft ?? $this->tooltipRight ?? $this->tooltipBottom;
        $this->tooltipPosition = $this->tooltipLeft ? 'lg:tooltip-left' : ($this->tooltipRight ? 'lg:tooltip-right' : ($this->tooltipBottom ? 'lg:tooltip-bottom' : 'lg:tooltip-top'));
    }

    public function render(): View|Closure|string
    {
        return <<<'HTML'
                <div
                    {{ $attributes->maryClass(["bg-base-100 rounded-lg px-5 py-4  w-full", "lg:tooltip $tooltipPosition" => $tooltip]) }}

                    @if($tooltip)
                        data-tip="{{ $tooltip }}"
                    @endif
                >
                    <div class="{{ Mary::classes('flex items-center gap-3') }}">
                        @if($icon)
                            <div class="{{ Mary::classes()->addRaw($color) }}">
                                <x-mary-icon :name="$icon" class="{{ Mary::classes('w-9 h-9') }}" />
                            </div>
                        @endif

                        <div class="{{ Mary::classes('text-left rtl:text-right truncate') }}">
                            @if($title)
                                <div class="{{ Mary::classes('text-xs text-base-content/50 whitespace-nowrap') }}">{{ $title }}</div>
                            @endif

                            <div class="{{ Mary::classes('font-black text-xl') }}">{{ $value ?? $slot }}</div>

                            @if($description)
                                <div class="{{ Mary::classes('stat-desc') }}">{{ $description }}</div>
                            @endif
                        </div>
                    </div>
                </div>
            HTML;
    }
}
