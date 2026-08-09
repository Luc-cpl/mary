<?php

namespace Mary\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Illuminate\View\Component;

class Header extends Component
{
    public string $anchor = '';

    public string $titleTag = 'div';

    public function __construct(
        public ?string $title = null,
        public ?string $subtitle = null,
        public ?bool $separator = false,
        public ?string $progressIndicator = null,
        public ?string $progressIndicatorClass = null,
        public ?bool $withAnchor = false,
        public ?string $size = null,
        public ?string $weight = null,
        public ?bool $useH1 = false,

        // Icon
        public ?string $icon = null,
        public ?string $iconClasses = null,

        // Slots
        public mixed $middle = null,
        public mixed $actions = null,
    ) {
        $this->anchor = Str::slug($title);
        $this->titleTag = $useH1 ? 'h1' : 'div';
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
                <div id="{{ $anchor }}" {{ $attributes->class(Mary::classes('mb-10')->addRaw(['mary-header-anchor' => $withAnchor])) }}>
                    <div class="{{ Mary::classes('flex flex-wrap gap-5 justify-between items-center') }}">
                        <div>
                            {!! "<{$titleTag}" !!} class="{{ Mary::classes('flex items-center')->add(is_null($size) ? 'text-2xl' : null)->add(is_null($weight) ? 'font-extrabold' : null)->addRaw($size)->addRaw($weight)->addRaw(is_string($title) ? '' : $title?->attributes->get('class')) }}" >
                                @if($withAnchor)
                                    <a href="#{{ $anchor }}">
                                @endif

                                @if($icon)
                                    <x-mary-icon name="{{ $icon }}" class="{{ $iconClasses }}" />
                                @endif

                                <span @maryClass(["ml-2" => $icon])>{{ $title }}</span>

                                @if($withAnchor)
                                    </a>
                                @endif
                            {!! "</{$titleTag}>" !!}

                            @if($subtitle)
                                <div class="{{ Mary::classes('text-base-content/50 text-sm mt-1')->addRaw(is_string($subtitle) ? '' : $subtitle?->attributes->get('class')) }}" >
                                    {{ $subtitle }}
                                </div>
                            @endif
                        </div>

                        @if($middle)
                            <div class="{{ Mary::classes('flex items-center justify-center gap-3 grow order-last sm:order-none')->addRaw(is_string($middle) ? '' : $middle?->attributes->get('class')) }}">
                                <div class="{{ Mary::classes('w-full lg:w-auto') }}">
                                    {{ $middle }}
                                </div>
                            </div>
                        @endif

                        @if($actions)
                            <div class="{{ Mary::classes('flex items-center gap-3')->addRaw(is_string($actions) ? '' : $actions?->attributes->get('class')) }}" >
                                {{ $actions }}
                            </div>
                        @endif
                    </div>

                    @if($separator)
                        <hr class="{{ Mary::classes('border-t-[length:var(--border)] border-base-content/10 mt-3') }}" />

                        @if($progressIndicator)
                            <div class="{{ Mary::classes('h-0.5 -mt-4 mb-4') }}">
                                <progress
                                    class="{{ Mary::classes('progress w-full h-[var(--border)]')->add(is_null($progressIndicatorClass) ? 'progress-primary' : null)->addRaw($progressIndicatorClass) }}"
                                    wire:loading

                                    @if($progressTarget())
                                        wire:target="{{ $progressTarget() }}"
                                     @endif></progress>
                            </div>
                        @endif
                    @endif
                </div>
                HTML;
    }
}