<?php

namespace Mary\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Swap extends Component
{
    public string $uuid;

    public function __construct(
        public ?string $id = null,
        public ?string $true = null,
        public ?string $false = null,
        public ?string $trueIcon = 'o-sun',
        public ?string $falseIcon = 'o-moon',
        public ?string $iconSize = null,
    ) {
        $this->uuid = 'mary' . md5(serialize($this)) . $id;
    }

    public function render(): View|Closure|string
    {
        return <<<'BLADE'
                <label
                    for="{{ $uuid }}"
                    {{ $attributes->whereDoesntStartWith('wire:model') }}>

                    {{-- Before --}}
                    @isset ($before)
                        <div {{ $before->attributes }}>
                            {{ $before }}
                        </div>
                    @endif

                    <div class="{{ Mary::classes('swap') }}">

                        {{-- Hidden checkbox for state --}}
                        <input id="{{ $uuid }}" type="checkbox" {{ $attributes->wire('model') }} />

                        {{-- True Element --}}
                        @isset ($true)
                            <div {{ is_string($true) ? new Illuminate\View\ComponentAttributeBag(['class' => Mary::classes('swap-on')]) : $true->attributes->class(Mary::classes('swap-on')) }}>
                                {{ $true ?? '' }}
                            </div>
                        @else
                            <x-mary-icon :name="$trueIcon" class="{{ Mary::classes('swap-on')->add(is_null($iconSize) ? 'h-5 w-5' : null)->addRaw($iconSize) }}" />
                        @endif

                        {{-- False Element --}}
                        @isset ($false)
                        <div {{ is_string($false) ? new Illuminate\View\ComponentAttributeBag(['class' => Mary::classes('swap-off')]) : $false->attributes->class(Mary::classes('swap-off')) }}>
                                {{ $false ?? '' }}
                            </div>
                        @else 
                            <x-mary-icon :name="$falseIcon" class="{{ Mary::classes('swap-off')->add(is_null($iconSize) ? 'h-5 w-5' : null)->addRaw($iconSize) }}" />
                        @endif

                    </div>

                    {{-- After --}}
                    @isset ($after)
                        <div {{ $after->attributes }}>
                            {{ $after }}
                        </div>
                    @endif
                </label>
            BLADE;
    }
}
