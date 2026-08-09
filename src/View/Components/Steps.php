<?php

namespace Mary\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Steps extends Component
{
    public string $uuid;

    public function __construct(
        public ?string $id = null,
        public bool $vertical = false,
        public ?string $stepsColor = null,
        public ?string $stepperClasses = null

    ) {
        $this->uuid = 'mary' . md5(serialize($this)) . $id;
    }

    public function render(): View|Closure|string
    {
        return <<<'BLADE'
                <div
                        x-data="{
                                steps: [],
                                current: @entangle($attributes->wire('model')),
                                init() {
                                    // Fix weird issue when navigating back
                                    document.addEventListener('livewire:navigating', () => {
                                        document.querySelectorAll('.' + CSS.escape('{{ Mary::classes('step') }}')).forEach(el => el.remove());
                                    });
                                }
                        }"
                    >
                        <!-- STEP LABELS -->
                        <ul class="{{ Mary::classes('steps [&>*:nth-child(2)]:before:hidden')->addRaw($stepperClasses) }}">
                            <template x-for="(step, index) in steps" :key="index">
                                <li
                                    class="{{ Mary::classes('step') }}"
                                    :data-content="!step.icon ? step.dataContent || (index + 1) : ''"
                                    :class="(index + 1 <= current) && '{{ is_null($stepsColor) ? Mary::classes('step-neutral') : Mary::classes()->addRaw($stepsColor) }} ' + step.classes"
                                >
                                        <template x-if="step.icon">
                                            <span x-html="step.icon" class="{{ Mary::classes('step-icon') }}"></span>
                                        </template>
                                        <span x-html="step.text"></span>
                                </li>
                            </template>
                        </ul>

                        <!-- STEP PANELS-->
                        <div {{ $attributes->whereDoesntStartWith('wire') }}>
                            {{ $slot }}
                        </div>

                        <!-- Force Tailwind compile steps color -->
                        <span class="{{ Mary::classes('hidden step-primary step-error step-success step-neutral step-info step-accent') }}"></span>
                    </div>
            BLADE;
    }
}
