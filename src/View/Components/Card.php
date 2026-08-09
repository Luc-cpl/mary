<?php

namespace Mary\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Card extends Component
{
    public string $uuid;

    public function __construct(
        public ?string $id = null,
        public ?string $title = null,
        public ?string $subtitle = null,
        public ?bool $separator = false,
        public ?bool $shadow = false,
        public ?string $progressIndicator = null,

        // Slots
        public mixed $menu = null,
        public mixed $actions = null,
        public mixed $figure = null,
        public ?string $bodyClass = 'null'
    ) {
        $this->uuid = 'mary' . md5(serialize($this)) . $id;
    }

    public function progressTarget(): ?string
    {
        if ($this->progressIndicator == 1) {
            return $this->attributes->whereStartsWith('progress-indicator')->first();
        }

        return $this->progressIndicator;
    }

    public function render(): View|Closure|string
    {
        return <<<'HTML'
                <div
                    {{
                        $attributes
                            ->merge(['wire:key' => $uuid ])
                            ->maryClass(['card bg-base-100 p-5', 'shadow-xs' => $shadow])
                    }}
                >
                    @if($figure)
                        <figure {{ $figure->attributes->maryClass(["mb-5 -m-5"]) }}>
                            {{ $figure }}
                        </figure>
                    @endif

                    @if($title || $subtitle)
                        <div class="{{ Mary::classes('pb-5') }}">
                            <div class="{{ Mary::classes('flex gap-3 justify-between items-center w-full') }}">
                                <div class="{{ Mary::classes('grow-1') }}">
                                    @if($title)
                                        <div class="{{ Mary::classes('text-xl font-bold')->addRaw(is_string($title) ? '' : $title?->attributes->get('class')) }}" >
                                            {{ $title }}
                                        </div>
                                    @endif
                                    @if($subtitle)
                                    <div class="{{ Mary::classes('text-base-content/50 text-sm mt-1')->addRaw(is_string($subtitle) ? '' : $subtitle?->attributes->get('class')) }}" >
                                            {{ $subtitle }}
                                        </div>
                                    @endif
                                </div>

                                @if($menu)
                                    <div {{ $menu->attributes->maryClass(["flex items-center gap-2"]) }}> {{ $menu }} </div>
                                @endif
                            </div>

                            @if($separator)
                                <hr class="{{ Mary::classes('mt-3 border-t-[length:var(--border)] border-base-content/10') }}" />

                                @if($progressIndicator)
                                    <div class="{{ Mary::classes('h-0.5 -mt-4 mb-4') }}">
                                        <progress
                                            class="{{ Mary::classes('progress progress-primary w-full h-0.5') }}"
                                            wire:loading

                                            @if($progressTarget())
                                                wire:target="{{ $progressTarget() }}"
                                             @endif></progress>
                                    </div>
                                @endif
                            @endif
                        </div>
                    @endif

                    <div class="{{ Mary::classes('grow-1')->addRaw($bodyClass) }}">
                        {{ $slot }}
                    </div>

                    @if($actions)
                        @if($separator)
                            <hr class="{{ Mary::classes('mt-5 border-t-[length:var(--border)] border-base-content/10') }}" />
                        @else
                            <div></div>
                        @endif

                        <div class="{{ Mary::classes('flex w-full items-end justify-end gap-3 pt-5')->addRaw(is_string($actions) ? '' : $actions?->attributes->get('class')) }}">
                            {{ $actions }}
                        </div>
                    @endif
                </div>
            HTML;
    }
}
