<?php

namespace Mary\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class MenuTitle extends Component
{
    public string $uuid;

    public function __construct(
        public ?string $id = null,
        public ?string $title = null,
        public ?string $icon = null,
        public ?string $iconClasses = null,
    ) {
        $this->uuid = 'mary' . md5(serialize($this)) . $id;
    }

    public function render(): View|Closure|string
    {
        return <<<'BLADE'
                <li {{ $attributes->maryClass(["menu-title"]) }}>
                    <div class="{{ Mary::classes('flex items-center gap-2') }}">

                        @if($icon)
                            <x-mary-icon :name="$icon" class="{{ Mary::classes()->addRaw($iconClasses) }}" />
                        @endif

                        {{ $title }}
                    </div>
                </li>
            BLADE;
    }
}
