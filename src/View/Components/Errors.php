<?php

namespace Mary\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Errors extends Component
{
    public string $uuid;

    public function __construct(
        public ?string $id = null,
        public ?string $title = null,
        public ?string $description = null,
        public ?string $icon = 'o-x-circle',
        public ?array $only = [],
    ) {
        $this->uuid = 'mary' . md5(serialize($this)) . $id;
    }

    public function render(): View|Closure|string
    {
        return <<<'BLADE'
                @if ($errors->any())
                    <div>
                        <div {{ $attributes->maryClass(["alert alert-error rounded rounded-sm"]) }} >
                            <div class="{{ Mary::classes('grid gap-3') }}">
                                <div class="{{ Mary::classes('flex gap-2') }}">
                                    @if($title)
                                        <x-mary-icon :name="$icon" class="{{ Mary::classes('w-6 h-6 mt-0.5') }}" />
                                    @endif
                                    <div>
                                        @if($title)
                                            <div class="{{ Mary::classes('font-bold text-lg') }}">{{ $title }}</div>
                                        @endif

                                        @if($description)
                                            <div class="{{ Mary::classes('font-semibold') }}">{{ $description }}</div>
                                        @endif
                                    </div>
                                </div>
                                <div>
                                    <ul class="{{ Mary::classes('list-disc ms-5 space-y-2 sm:ms-12 pb-3') }}">
                                       @foreach ($errors->all() as $error)
                                           <li>{{ $error }}</li>
                                       @endforeach
                                    </ul>
                                </div>
                            </div>
                        </div>
                </div>
            @endif
            BLADE;
    }
}
