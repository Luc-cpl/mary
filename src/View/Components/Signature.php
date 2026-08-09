<?php

namespace Mary\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Signature extends Component
{
    public string $uuid;

    public function __construct(
        public ?string $id = null,
        public ?string $height = '250',
        public ?string $clearText = 'Clear',
        public ?string $hint = null,
        public ?string $hintClass = null,
        public ?array $config = [],
        public ?string $clearBtnStyle = null,

        // Validations
        public ?string $errorClass = null,
        public ?string $errorField = null,
        public ?bool $omitError = false,
        public ?bool $firstErrorOnly = false,
    ) {
        $this->uuid = 'mary' . md5(serialize($this)) . $id;
    }

    public function modelName(): ?string
    {
        return $this->attributes->whereStartsWith('wire:model')->first();
    }

    public function errorFieldName(): ?string
    {
        return $this->errorField ?? $this->modelName();
    }

    public function setup(): string
    {
        return json_encode(array_merge([], $this->config));
    }

    public function render(): View|Closure|string
    {
        return <<<'HTML'
                <div>
                    <div
                        x-data="{
                            value: @entangle($attributes->wire('model')),
                            signature: null,
                            init() {
                                let canvas = document.getElementById('{{ $uuid }}signature')
                                this.signature = new SignaturePad(canvas, {{ $setup() }});

                                // Resize
                                const ratio = Math.max(window.devicePixelRatio || 1, 1);
                                canvas.width = canvas.offsetWidth * ratio;
                                canvas.height = canvas.offsetHeight * ratio;
                                canvas.getContext('2d').scale(ratio, ratio);
                                this.signature.fromData(this.signature.toData());

                                // Event
                                this.signature.addEventListener('endStroke', () =>  this.extract() );
                            },
                            extract() {
                                this.value = this.signature.toDataURL();
                            },
                            clear() {
                                this.signature.clear();
                                this.value = null;
                            }
                         }"

                         wire:ignore
                         class="{{ Mary::classes('select-none touch-none block') }}"
                    >
                        <div
                            {{
                                $attributes
                                    ->except("wire:model")
                                    ->maryClass([
                                        "border-[length:var(--border)] border-base-300 rounded-lg relative bg-white select-none touch-none block",
                                        "!border-error" => $errors->has($modelName())
                                    ])
                            }}
                        }>
                            <canvas id="{{ $uuid }}signature" height="{{ $height }}" class="{{ Mary::classes('rounded-lg block w-full select-none touch-none') }}"></canvas>

                            <!-- CLEAR BUTTON -->
                            <div class="{{ Mary::classes('absolute end-2 top-1/2 -translate-y-1/2 ') }}">
                                <x-mary-button icon="o-backspace" :label="$clearText" @click="clear" class="{{ is_null($clearBtnStyle) ? Mary::classes('btn-sm btn-ghost') : Mary::classes()->addRaw($clearBtnStyle) }}" />
                            </div>
                        </div>
                    </div>

                    <!-- ERROR -->
                    @if(!$omitError && $errors->has($errorFieldName()))
                        @foreach($errors->get($errorFieldName()) as $message)
                            @foreach(Arr::wrap($message) as $line)
                                <div class="{{ is_null($errorClass) ? Mary::classes('text-error text-xs pt-1') : Mary::classes()->addRaw($errorClass) }}" x-classes="{{ Mary::classes('text-error text-xs pt-1') }}">{{ $line }}</div>
                                @break($firstErrorOnly)
                            @endforeach
                            @break($firstErrorOnly)
                        @endforeach
                    @endif

                    <!-- HINT -->
                    @if($hint)
                        <div class="{{ is_null($hintClass) ? Mary::classes('fieldset-label text-xs pt-1') : Mary::classes()->addRaw($hintClass) }}" x-classes="{{ Mary::classes('fieldset-label text-xs pt-1') }}">{{ $hint }}</div>
                    @endif
                </div>
            HTML;
    }
}
