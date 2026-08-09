<div
    {{ Mary::classes('btn hover:bg-primary')
        ->add([
            'flex gap-2',
            'hidden' => session('condition-value-is-not-a-class'),
            'opacity-50' => false,
        ])
        ->addRaw('consumer-class')
    }}
>
    {{ app('mary')->classes()->add(is_null($size) ? 'h-4 w-4' : null) }}
    {{ unrelated_builder()->add('not-a-mary-class') }}
</div>
