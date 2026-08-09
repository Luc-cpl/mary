<?php

use Mary\Traits\Toast;

it('prefixes toast defaults inside the JavaScript payload', function () {
    config()->set('mary.tailwind_prefix', 'tw');

    $host = new class
    {
        use Toast;

        public string $script = '';

        public function js(string $script): void
        {
            $this->script = $script;
        }
    };

    $host->success('Saved');

    expect($host->script)->toContain('tw:alert-success')
        ->toContain('tw:w-7')
        ->toContain('tw:h-7');
});

it('keeps toast classes explicitly supplied by the application raw', function () {
    config()->set('mary.tailwind_prefix', 'tw');

    $host = new class
    {
        use Toast;

        public string $script = '';

        public function js(string $script): void
        {
            $this->script = $script;
        }
    };

    $host->success('Saved', css: 'consumer-toast', progressClass: 'consumer-progress');

    expect($host->script)->toContain('consumer-toast')
        ->toContain('consumer-progress')
        ->not->toContain('tw:consumer-toast')
        ->not->toContain('tw:consumer-progress');
});
