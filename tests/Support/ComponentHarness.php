<?php

namespace Mary\Tests\Support;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\ComponentSlot;
use Livewire\Component;

class ComponentHarness extends Component
{
    public string $template = '';

    public function mount(): void
    {
        if (str_contains($this->template, '<x-errors')) {
            $this->addError('field', 'Validation error');
        }
    }

    public function paginator(): LengthAwarePaginator
    {
        return new LengthAwarePaginator([], 0, 10);
    }

    public function slot(string $contents): ComponentSlot
    {
        return new ComponentSlot($contents);
    }

    public function render(): string
    {
        return '<div>'.$this->template.'</div>';
    }
}
