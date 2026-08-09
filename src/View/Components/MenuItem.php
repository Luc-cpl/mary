<?php

namespace Mary\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Illuminate\View\Component;

class MenuItem extends Component
{
    public string $uuid;

    public function __construct(
        public ?string $id = null,
        public ?string $title = null,
        public ?string $icon = null,
        public ?string $iconClasses = null,
        public ?string $spinner = null,
        public ?string $link = null,
        public ?string $route = null,
        public ?array $routeParams = [],
        public ?bool $external = false,
        public ?bool $noWireNavigate = false,
        public ?string $badge = null,
        public ?string $badgeClasses = null,
        public ?bool $active = false,
        public ?bool $separator = false,
        public ?bool $hidden = false,
        public ?bool $disabled = false,
        public ?bool $exact = false
    ) {
        $this->uuid = 'mary' . md5(serialize($this)) . $id;
    }

    public function spinnerTarget(): ?string
    {
        if ($this->spinner == 1) {
            return $this->attributes->whereStartsWith('wire:click')->first();
        }

        return $this->spinner;
    }

    public function getHref(): ?string
    {
        if ($this->route) {
            return route($this->route, $this->routeParams);
        }

        return $this->link;
    }

    public function routeMatches(): bool
    {
        $href = $this->getHref();

        if ($href == null) {
            return false;
        }

        if ($this->route) {
            return request()->routeIs($this->route);
        }

        $link = url($this->link ?? '');
        $route = url(request()->url());

        if ($link == $route) {
            return true;
        }

        return ! $this->exact && $this->link != '/' && Str::startsWith($route, $link);
    }

    public function render(): View|Closure|string
    {
        if ($this->hidden === true) {
            return '';
        }

        return <<<'BLADE'
                @aware(['horizontal' => false, 'activateByRoute' => false, 'activeBgColor' => null])

                @php
                    $isActive = $active || ($activateByRoute && $routeMatches());
                @endphp

                <li @maryClass(['menu-disabled' => $disabled])>
                    <a
                        {{
                            $attributes->class(
                                Mary::classes('my-0.5 py-1.5 px-4 hover:text-inherit whitespace-nowrap')
                                    ->add($isActive && is_null($activeBgColor) ? 'bg-base-300' : null)
                                    ->addRaw(['mary-active-menu' => $isActive])
                                    ->addRaw($isActive ? $activeBgColor : null)
                            )
                        }}

                        @if($getHref())
                            href="{{ $getHref() }}"

                            @if($external)
                                target="_blank"
                            @endif

                            @if(!$external && !$noWireNavigate)
                                {{ $attributes->wire('navigate')->value() ? $attributes->wire('navigate') : 'wire:navigate' }}
                            @endif
                        @endif

                        @if($spinner)
                            wire:target="{{ $spinnerTarget() }}"
                            wire:loading.attr="disabled"
                        @endif
                    >
                        {{-- SPINNER --}}
                        @if($spinner)
                            <span wire:loading wire:target="{{ $spinnerTarget() }}" class="{{ Mary::classes(['loading loading-spinner loading-xs w-5 h-5', 'my-1' => $icon]) }}"></span>
                        @endif

                        @if($icon)
                            <span class="{{ Mary::classes('block py-0.5') }}" @if($spinner) wire:loading.class="{{ Mary::classes('hidden') }}" wire:target="{{ $spinnerTarget() }}" @endif>
                                <x-mary-icon :name="$icon" class="{{ Mary::classes('mb-0.5')->addRaw($iconClasses) }}" />
                            </span>
                        @endif

                        @if($title || $slot->isNotEmpty())
                        <span class="{{ Mary::classes(['whitespace-nowrap', 'truncate' => ! $horizontal])->addRaw('mary-hideable') }}">
                            @if($title)
                                {{ $title }}

                                @if($badge)
                                    <span class="{{ Mary::classes('badge badge-sm')->addRaw($badgeClasses) }}">{{ $badge }}</span>
                                @endif
                            @else
                                {{ $slot }}
                            @endif
                        </span>
                        @endif
                    </a>
                </li>
            BLADE;
    }
}
