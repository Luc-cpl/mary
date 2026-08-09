<?php

namespace Mary\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Checkbox extends Component
{
    public string $uuid;

    public function __construct(
        public ?string $id = null,
        public ?string $label = null,
        public ?bool $right = false,
        public ?string $hint = null,
        public ?string $hintClass = null,

        // Validations
        public ?string $errorField = null,
        public ?string $errorClass = null,
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

    public function render(): View|Closure|string
    {
        return <<<'BLADE'
            <div>
                <fieldset class="{{ Mary::classes('fieldset') }}">
                    <div class="{{ Mary::classes('w-full') }}">
                        <label @maryClass(["flex gap-3 items-center cursor-pointer", "justify-between" => $right, "!items-start" => $hint])>

                            {{-- CHECKBOX --}}
                            <input
                                id="{{ $uuid }}"
                                type="checkbox"
                                {{
                                    $attributes->whereDoesntStartWith("id")
                                        ->maryClass(["order-2" => $right])
                                        ->class(Mary::classes('checkbox'))
                                 }}
                            />

                            {{-- LABEL --}}
                             <div @maryClass(["order-1" => $right])>
                                <div class="{{ Mary::classes('text-sm font-medium') }}">
                                    {{ $label }}

                                    @if($attributes->get('required'))
                                        <span class="{{ Mary::classes('text-error') }}">*</span>
                                    @endif
                                </div>

                                {{-- HINT --}}
                                @if($hint)
                                    <div class="{{ is_null($hintClass) ? Mary::classes('fieldset-label') : Mary::classes()->addRaw($hintClass) }}" x-classes="{{ Mary::classes('fieldset-label') }}">{{ $hint }}</div>
                                @endif
                            </div>
                        </label>
                    </div>

                    {{-- ERROR --}}
                    @if(!$omitError && $errors->has($errorFieldName()))
                        @foreach($errors->get($errorFieldName()) as $message)
                            @foreach(Arr::wrap($message) as $line)
                                <div class="{{ is_null($errorClass) ? Mary::classes('text-error') : Mary::classes()->addRaw($errorClass) }}" x-class="{{ Mary::classes('text-error') }}">{{ $line }}</div>
                                @break($firstErrorOnly)
                            @endforeach
                            @break($firstErrorOnly)
                        @endforeach
                    @endif
                </fieldset>
            </div>
            BLADE;
    }
}
