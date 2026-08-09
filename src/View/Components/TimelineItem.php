<?php

namespace Mary\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class TimelineItem extends Component
{
    public string $uuid;

    public function __construct(
        public string $title,
        public ?string $id = null,
        public ?string $subtitle = null,
        public ?string $description = null,
        public ?string $icon = null,
        public ?bool $pending = false,
        public ?bool $first = false,
        public ?bool $last = false,

        public ?string $connectorPendingClass = null,
        public ?string $connectorActiveClass = null,
        public ?string $bulletActiveClass = null,
        public ?string $bulletPendingClass = null,

    ) {
        $this->uuid = 'mary' . md5(serialize($this)) . $id;
    }

    public function render(): View|Closure|string
    {
        return <<<'HTML'
                <div>
                    <!-- Last item `border cut` -->
                    <div class="{{ Mary::classes([
                            'border-s-2 h-5 -mb-5' => $last,
                            'border-s-base-300' => $last && is_null($connectorPendingClass),
                            '!border-s-primary' => ! $pending && is_null($connectorActiveClass),
                        ])->addRaw($last ? $connectorPendingClass : null)
                          ->addRaw(! $pending ? $connectorActiveClass : null) }}">
                    </div>

                    <!-- WRAPPER THAT ALSO ACTS A LINE CONNECTOR -->
                    <div class="{{ Mary::classes([
                            'border-s-2 ps-8 py-3',
                            'border-s-base-300' => is_null($connectorPendingClass),
                            '!border-s-primary' => ! $pending && is_null($connectorActiveClass),
                            'pt-0' => $first,
                            '!border-s-0' => $last,
                        ])->addRaw($connectorPendingClass)
                          ->addRaw(! $pending ? $connectorActiveClass : null) }}">
                        <!-- BULLET -->
                        <div class="{{ Mary::classes([
                                'w-4 h-4 -mb-5 -ms-[41px] rounded-full',
                                'bg-base-300' => is_null($bulletPendingClass),
                                '!bg-primary' => ! $pending && is_null($bulletActiveClass),
                                '!-ms-[39px]' => $last,
                                'w-8 h-8 !-ms-[48px] -mb-7' => $icon,
                                '-ms-[46px]!' => $last && $icon,
                            ])->addRaw($bulletPendingClass)
                              ->addRaw(! $pending ? $bulletActiveClass : null) }}">
                            <!-- ICON -->
                            @if($icon)
                                <x-mary-icon :name="$icon" class="{{ Mary::classes(['ms-2 mt-1 w-4 h-4', 'text-base-100' => ! $pending]) }}" />
                            @endif
                        </div>

                        <!-- TITLE -->
                        <div @maryClass(["font-bold mb-1"])>{{ $title }}</div>

                        <!-- SUBTITLE -->
                        @if($subtitle)
                            <div class="{{ Mary::classes('text-xs text-base-content/30 font-bold') }}">{{ $subtitle }}</div>
                        @endif

                        <!-- DESCRIPTION -->
                        @if($description)
                            <div class="{{ Mary::classes('text-sm mt-3') }}">
                                {{ $description }}
                            </div>
                        @endif
                    </div>
                </div>
            HTML;
    }
}
