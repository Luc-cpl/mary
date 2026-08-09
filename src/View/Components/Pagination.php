<?php

namespace Mary\View\Components;

use ArrayAccess;
use Closure;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Pagination extends Component
{
    public string $uuid;

    public function __construct(
        public ArrayAccess|array $rows,
        public ?string $id = null,
        public ?array $perPageValues = [10, 20, 50, 100],
    ) {
        $this->uuid = 'mary' . md5(serialize($this)) . $id;
    }

    public function modelName(): ?string
    {
        return $this->attributes->whereStartsWith('wire:model.live')->first();
    }

    public function isShowable(): bool
    {
        return ! empty($this->modelName()) && $this->rows instanceof LengthAwarePaginator && $this->rows->isNotEmpty();
    }

    public function render(): View|Closure|string
    {
        return <<<'HTML'
            <div class="{{ Mary::classes()->addRaw('mary-table-pagination') }}">
                <div {{ $attributes->maryClass(["mb-4 border-t-[length:var(--border)] border-t-base-content/5"]) }}></div>
                <div class="{{ Mary::classes('justify-between md:flex md:flex-row w-auto md:w-full items-center overflow-y-auto ps-2 pe-2 relative') }}">
                    @if($isShowable())
                    <div class="{{ Mary::classes('flex flex-row justify-center md:justify-start mb-2 md:mb-0 py-1') }}">
                        <select id="{{ $uuid }}" @if(!empty($modelName())) wire:model.live="{{ $modelName() }}" @endif
                                class="{{ Mary::classes('select select-sm flex sm:text-sm sm:leading-6 w-auto md:mr-5') }}">
                            @foreach ($perPageValues as $option)
                            <option value="{{ $option }}" @selected($rows->perPage() === $option)>{{ $option }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endif
                    <div class="{{ Mary::classes('w-full') }}">
                    @if($rows instanceof LengthAwarePaginator)
                        {{ $rows->onEachSide(1)->links(data: ['scrollTo' => false]) }}
                    @else
                        {{ $rows->links(data: ['scrollTo' => false]) }}
                    @endif
                    </div>
                </div>
            </div>
            HTML;
    }
}
