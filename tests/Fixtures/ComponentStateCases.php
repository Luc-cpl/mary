<?php

return [
    'input validation, readonly state, adornments and consumer overrides' => [
        'template' => <<<'BLADE'
            <x-input
                wire:model="name"
                label="Name"
                hint="Use your full name"
                hint-class="consumer-hint"
                error-class="consumer-error"
                prefix="$"
                suffix="USD"
                icon="o-user"
                icon-right="o-check"
                clearable
                required
                readonly
            />
            BLADE,
        'errors' => ['name' => ['Name is required']],
        'internal' => [
            'fieldset', 'py-0', 'fieldset-legend', 'mb-0.5', 'text-error', 'w-full',
            'input', 'border-dashed', '!input-error', 'label', 'pointer-events-none',
            'cursor-pointer', 'opacity-40',
        ],
        'raw' => ['consumer-error', 'consumer-hint'],
    ],
    'modal title, separator, actions and box override' => [
        'template' => <<<'BLADE'
            <x-modal id="account-modal" title="Account" subtitle="Details" separator box-class="consumer-modal-box">
                Content
                <x-slot:actions><x-button>Save</x-button></x-slot:actions>
            </x-modal>
            BLADE,
        'internal' => [
            'modal', 'modal-box', 'btn-circle', 'btn-sm', 'btn-ghost', 'absolute', 'end-2',
            'top-2', 'z-[999]', 'text-xl', '!mb-5', 'border-t-[length:var(--border)]',
            'border-base-content/10', 'mt-5', 'modal-action', 'modal-backdrop',
        ],
        'raw' => ['consumer-modal-box'],
    ],
    'active first and last timeline item with icon' => [
        'template' => <<<'BLADE'
            <x-timeline-item
                title="Released"
                subtitle="Today"
                description="Production"
                icon="o-check"
                first
                last
            />
            BLADE,
        'internal' => [
            'border-s-2', 'h-5', '-mb-5', 'border-s-base-300', '!border-s-primary', 'ps-8',
            'py-3', 'pt-0', '!border-s-0', 'w-8', 'h-8', '!-ms-[48px]', '-mb-7',
            '-ms-[46px]!', 'text-base-100', 'font-bold', 'text-xs', 'text-sm', 'mt-3',
        ],
    ],
    'pending timeline item with consumer state classes' => [
        'template' => <<<'BLADE'
            <x-timeline-item
                title="Pending"
                pending
                last
                connector-pending-class="consumer-connector-pending"
                connector-active-class="consumer-connector-active"
                bullet-pending-class="consumer-bullet-pending"
                bullet-active-class="consumer-bullet-active"
            />
            BLADE,
        'internal' => ['border-s-2', 'h-5', '-mb-5', 'ps-8', 'py-3', '!border-s-0', 'rounded-full'],
        'raw' => ['consumer-connector-pending', 'consumer-bullet-pending'],
        'absent' => ['border-s-base-300', '!border-s-primary', 'bg-base-300', '!bg-primary'],
    ],
    'external responsive button with right spinner and badge override' => [
        'template' => <<<'BLADE'
            <x-button
                label="Deploy"
                link="https://example.test"
                external
                responsive
                icon-right="o-arrow-right"
                spinner="deploy"
                badge="New"
                badge-classes="consumer-badge"
            />
            BLADE,
        'internal' => [
            'btn', 'block', 'hidden', 'lg:block', 'badge', 'badge-sm', 'loading',
            'loading-spinner', 'w-5', 'h-5',
        ],
        'raw' => ['consumer-badge'],
    ],
    'choices height override remains application controlled' => [
        'template' => '<x-choices wire:model="choice" :options="[]" height="consumer-height" />',
        'livewire' => true,
        'internal' => ['select', 'w-full', 'absolute', 'z-10', 'overflow-y-auto'],
        'raw' => ['consumer-height'],
        'absent' => ['max-h-64'],
    ],
    'offline choices height override remains application controlled' => [
        'template' => '<x-choices-offline wire:model="choice" :options="[]" height="consumer-height" />',
        'livewire' => true,
        'internal' => ['select', 'w-full', 'absolute', 'z-10', 'overflow-y-auto'],
        'raw' => ['consumer-height'],
        'absent' => ['max-h-64'],
    ],
    'scrollable dropdown uses its internal default maximum height' => [
        'template' => <<<'BLADE'
            <x-dropdown scroll>
                <x-slot:trigger>Open</x-slot:trigger>
                Content
            </x-dropdown>
            BLADE,
        'internal' => ['dropdown', 'menu', 'max-h-96', 'overflow-y-auto'],
    ],
];
