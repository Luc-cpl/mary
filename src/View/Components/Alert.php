<?php

namespace Mary\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Alert extends Component
{
    public string $uuid;

    /**
     * @param ?string  $title  The title of the alert, displayed in bold.
     * @param ?string  $icon  The icon displayed at the beginning of the alert.
     * @param ?string  $description  A short description under the title.
     * @param ?bool  $shadow  Whether to apply a shadow effect to the alert.
     * @param ?bool  $dismissible  Whether the alert can be dismissed by the user.
     * @slot  mixed  $actions  Slots for actionable elements like buttons or links.
     */
    public function __construct(
        public ?string $id = null,
        public ?string $title = null,
        public ?string $icon = null,
        public ?string $description = null,
        public ?bool $shadow = false,
        public ?bool $dismissible = false,

        // Slots
        public mixed $actions = null
    ) {
        $this->uuid = 'mary' . md5(serialize($this)) . $id;
    }

    public function render(): View|Closure|string
    {
        return <<<'BLADE'
                <div
                    wire:key="{{ $uuid }}"
                    {{ $attributes->whereDoesntStartWith('class') }}
                    {{ $attributes->maryClass(['alert rounded-md', 'shadow-md' => $shadow])}}
                    x-data="{ show: true }" x-show="show"
                >
                    @if($icon)
                        <x-mary-icon :name="$icon" class="{{ Mary::classes('self-center') }}" />
                    @endif

                    @if($title)
                        <div>
                            <div @maryClass(["font-bold" => $description])>{{ $title }}</div>
                            <div class="{{ Mary::classes('text-xs') }}">{{ $description }}</div>
                        </div>
                    @else
                        <span>{{ $slot }}</span>
                    @endif

                    <div class="{{ Mary::classes('flex items-center gap-3') }}">
                        {{ $actions }}
                    </div>

                    @if($dismissible)
                        <x-mary-button icon="o-x-mark" @click="show = false" class="{{ Mary::classes('btn-xs btn-circle btn-ghost static self-start end-0') }}" />
                    @endif
                </div>
            BLADE;
    }
}
