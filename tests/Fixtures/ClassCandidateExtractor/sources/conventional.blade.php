{{-- Mary::classes('blade-comment') --}}
<!-- @maryClass('blade-html-comment') -->

<section class="{{ Mary::classes('blade-view grid') }}">
    <div
        @maryClass(['blade-conditional' => $visible])
        {{ $attributes->maryClass('blade-attribute') }}
    >
        <span class="{{ app('mary')->classes('blade-app')->add('blade-chain') }}"></span>
    </div>
</section>
