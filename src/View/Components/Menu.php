<?php

namespace Mary\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Menu extends Component
{
    public string $uuid;

    public function __construct(
        public ?string $id = null,
        public ?string $title = null,
        public ?string $icon = null,
        public ?string $iconClasses = null,
        public ?bool $separator = false,
        public ?bool $activateByRoute = false,
        public ?string $activeBgColor = null,
        public ?bool $horizontal = false
    ) {
        $this->uuid = 'mary' . md5(serialize($this)) . $id;
    }

    public function render(): View|Closure|string
    {
        return <<<'BLADE'
                <ul {{ $attributes->maryClass(["menu w-full", "menu-horizontal flex-nowrap overflow-x-auto scrollbar-none" => $horizontal]) }} >
                    @if($title)
                        <li class="{{ Mary::classes('menu-title text-inherit uppercase') }}">
                            <div class="{{ Mary::classes('flex items-center gap-2') }}">

                                @if($icon)
                                    <x-mary-icon :name="$icon" class="{{ Mary::classes('inline-flex')->add(is_null($iconClasses) ? 'w-4 h-4' : null)->addRaw($iconClasses) }}" />
                                @endif

                                {{ $title }}
                            </div>
                        </li>
                    @endif

                    @if($separator)
                        <hr class="{{ Mary::classes('mb-3 border-t-[length:var(--border)] border-base-content/10') }}" />
                    @endif

                    {{ $slot }}
                </ul>
            BLADE;
    }
}
